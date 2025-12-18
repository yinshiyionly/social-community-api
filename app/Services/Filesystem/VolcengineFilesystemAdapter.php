<?php

namespace App\Services\Filesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use DateTimeInterface;
use Exception;
use Tos\TosClient;
use Tos\Exception\TosClientException;
use Tos\Exception\TosServerException;
use Tos\Model\PutObjectInput;
use Tos\Model\GetObjectInput;
use Tos\Model\HeadObjectInput;
use Tos\Model\DeleteObjectInput;
use Tos\Model\CopyObjectInput;
use Tos\Model\ListObjectsInput;
use Tos\Model\PutObjectACLInput;
use Tos\Model\GetObjectACLInput;
use Tos\Model\PreSignedURLInput;
use Tos\Model\Enum;

class VolcengineFilesystemAdapter implements AdapterInterface
{
    private TosClient $client;
    private string $bucket;
    private string $prefix;
    private array $config;

    /**
     * @var array
     */
    protected static $resultMap = [
        'Body' => 'contents',
        'ContentLength' => 'size',
        'ContentType' => 'mimetype',
        'Size' => 'size',
    ];

    public function __construct(TosClient $client, string $bucket, array $config = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? '';
        
        $this->validateConfiguration();
    }

    /**
     * Validate the configuration and throw exception if required settings are missing
     */
    private function validateConfiguration(): void
    {
        $requiredKeys = ['key', 'secret', 'region', 'bucket', 'endpoint'];
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (empty($this->config[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new Exception(
                'Missing required Volcengine TOS configuration: ' . implode(', ', $missingKeys)
            );
        }
    }

    /**
     * Apply path prefix
     */
    protected function applyPathPrefix(string $path): string
    {
        if (empty($this->prefix)) {
            return ltrim($path, '/');
        }
        
        return ltrim($this->prefix . '/' . ltrim($path, '/'), '/');
    }

    /**
     * Remove path prefix
     */
    protected function removePathPrefix(string $path): string
    {
        if (empty($this->prefix)) {
            return $path;
        }
        
        $prefixLength = strlen($this->prefix);
        if (strpos($path, $this->prefix) === 0) {
            return ltrim(substr($path, $prefixLength), '/');
        }
        
        return $path;
    }

    public function write($path, $contents, Config $config)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $input = new PutObjectInput($this->bucket, $prefixedPath, $contents);

            // Set content type if provided
            if ($contentType = $config->get('ContentType')) {
                $input->setContentType($contentType);
            }

            // Set visibility
            if ($visibility = $config->get('visibility')) {
                $acl = $visibility === 'public' ? 'public-read' : 'private';
                $input->setACL($acl);
            }

            $result = $this->client->putObject($input);

            // Return normalized response for successful upload
            return [
                'type' => 'file',
                'path' => $path,
                'dirname' => Util::dirname($path),
                'timestamp' => time(),
            ];
        } catch (TosServerException | TosClientException | Exception $e) {
            return false;
        }
    }

    public function writeStream($path, $resource, Config $config)
    {
        return $this->write($path, stream_get_contents($resource), $config);
    }

    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    public function rename($path, $newpath)
    {
        if (!$this->copy($path, $newpath)) {
            return false;
        }

        return $this->delete($path);
    }

    public function copy($path, $newpath)
    {
        try {
            $prefixedSource = $this->applyPathPrefix($path);
            $prefixedDestination = $this->applyPathPrefix($newpath);
            
            $input = new CopyObjectInput($this->bucket, $prefixedDestination);
            $input->setSrcBucket($this->bucket);
            $input->setSrcKey($prefixedSource);
            
            $this->client->copyObject($input);

            return true;
        } catch (TosServerException | TosClientException | Exception $e) {
            return false;
        }
    }

    public function delete($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $input = new DeleteObjectInput($this->bucket, $prefixedPath);
            $this->client->deleteObject($input);

            return true;
        } catch (TosServerException | TosClientException | Exception $e) {
            return false;
        }
    }

    public function deleteDir($dirname)
    {
        $prefixedPath = $this->applyPathPrefix($dirname);
        
        try {
            // List all objects with the prefix
            $input = new ListObjectsInput($this->bucket);
            $input->setPrefix(rtrim($prefixedPath, '/') . '/');
            
            $result = $this->client->listObjects($input);

            if ($result->getContents()) {
                // Delete objects one by one (TOS SDK might not have batch delete in this version)
                foreach ($result->getContents() as $object) {
                    $deleteInput = new DeleteObjectInput($this->bucket, $object->getKey());
                    $this->client->deleteObject($deleteInput);
                }
            }

            return true;
        } catch (TosServerException | TosClientException | Exception $e) {
            return false;
        }
    }

    public function createDir($dirname, Config $config)
    {
        // Object storage doesn't require explicit directory creation
        return ['path' => $dirname, 'type' => 'dir'];
    }

    public function setVisibility($path, $visibility)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $acl = $visibility === 'public' ? 'public-read' : 'private';
            $input = new PutObjectACLInput($this->bucket, $prefixedPath, $acl);
            
            $this->client->putObjectAcl($input);

            return compact('path', 'visibility');
        } catch (TosServerException | TosClientException | Exception $e) {
            return false;
        }
    }

    public function has($path)
    {
        return $this->getMetadata($path) !== false;
    }

    public function read($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $input = new GetObjectInput($this->bucket, $prefixedPath);
            $result = $this->client->getObject($input);

            return [
                'type' => 'file',
                'path' => $path,
                'contents' => $result->getContent()->getContents()
            ];
        } catch (TosServerException $e) {
            return false;
        } catch (TosClientException | Exception $e) {
            return false;
        }
    }

    public function readStream($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $input = new GetObjectInput($this->bucket, $prefixedPath);
            $result = $this->client->getObject($input);

            return [
                'type' => 'file',
                'path' => $path,
                'stream' => $result->getContent()->detach()
            ];
        } catch (TosServerException $e) {
            return false;
        } catch (TosClientException | Exception $e) {
            return false;
        }
    }

    public function listContents($directory = '', $recursive = false)
    {
        $prefixedPath = $this->applyPathPrefix($directory);
        
        try {
            $input = new ListObjectsInput($this->bucket);
            $input->setPrefix($prefixedPath ? rtrim($prefixedPath, '/') . '/' : '');

            if (!$recursive) {
                $input->setDelimiter('/');
            }

            $result = $this->client->listObjects($input);
            
            $contents = [];

            // Process files
            if ($result->getContents()) {
                foreach ($result->getContents() as $object) {
                    $path = $this->removePathPrefix($object->getKey());
                    
                    $contents[] = [
                        'type' => 'file',
                        'path' => $path,
                        'timestamp' => $object->getLastModified() ? strtotime($object->getLastModified()) : null,
                        'size' => $object->getSize() ?? null,
                    ];
                }
            }

            // Process directories (common prefixes)
            if ($result->getCommonPrefixes()) {
                foreach ($result->getCommonPrefixes() as $prefix) {
                    $path = rtrim($this->removePathPrefix($prefix->getPrefix()), '/');
                    
                    $contents[] = [
                        'type' => 'dir',
                        'path' => $path,
                    ];
                }
            }

            return $contents;
        } catch (TosServerException | TosClientException | Exception $e) {
            return [];
        }
    }

    public function getMetadata($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $input = new HeadObjectInput($this->bucket, $prefixedPath);
            $result = $this->client->headObject($input);

            return [
                'type' => 'file',
                'path' => $path,
                'timestamp' => $result->getLastModified() ? strtotime($result->getLastModified()) : null,
                'size' => $result->getContentLength() ?? null,
                'mimetype' => $result->getContentType() ?? null,
            ];
        } catch (TosServerException | TosClientException | Exception $e) {
            return false;
        }
    }

    public function getSize($path)
    {
        $metadata = $this->getMetadata($path);
        return $metadata ? Util::map($metadata, ['size']) : false;
    }

    public function getMimetype($path)
    {
        $metadata = $this->getMetadata($path);
        return $metadata ? Util::map($metadata, ['mimetype']) : false;
    }

    public function getTimestamp($path)
    {
        $metadata = $this->getMetadata($path);
        return $metadata ? Util::map($metadata, ['timestamp']) : false;
    }

    public function getVisibility($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $input = new GetObjectACLInput($this->bucket, $prefixedPath);
            $result = $this->client->getObjectAcl($input);

            $visibility = AdapterInterface::VISIBILITY_PRIVATE;
            if ($result->getGrants()) {
                foreach ($result->getGrants() as $grant) {
                    if ($grant->getGrantee() && $grant->getGrantee()->getURI() && 
                        strpos($grant->getGrantee()->getURI(), 'AllUsers') !== false) {
                        $visibility = AdapterInterface::VISIBILITY_PUBLIC;
                        break;
                    }
                }
            }

            return compact('path', 'visibility');
        } catch (TosServerException | TosClientException | Exception $e) {
            return false;
        }
    }

    /**
     * Get the URL for the file
     */
    public function getUrl(string $path): string
    {
        $prefixedPath = $this->applyPathPrefix($path);
        $schema = $this->config['schema'] ?? 'https';
        
        // Clean path
        $cleanPath = ltrim($prefixedPath, '/');
        
        // If CDN URL is configured (without protocol)
        if (!empty($this->config['url'])) {
            return $schema . '://' . rtrim($this->config['url'], '/') . '/' . $cleanPath;
        }

        // Otherwise, construct bucket.endpoint URL
        $endpoint = $this->config['endpoint'] ?? '';
        
        // Remove protocol from endpoint if present
        $endpointWithoutProtocol = preg_replace('/^https?:\/\//', '', $endpoint);
        
        return $schema . '://' . $this->bucket . '.' . $endpointWithoutProtocol . '/' . $cleanPath;
    }

    /**
     * Get a temporary URL for the file
     */
    public function getTemporaryUrl(string $path, DateTimeInterface $expiresAt, array $options = []): string
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $expires = $expiresAt->getTimestamp() - time();
            
            $input = new PreSignedURLInput(Enum::HttpMethodGet, $this->bucket, $prefixedPath);
            $input->setExpires($expires);
            
            $result = $this->client->preSignedURL($input);

            return $result->getSignedUrl();
        } catch (TosServerException | TosClientException | Exception $e) {
            throw new Exception("Unable to generate temporary URL: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Normalize the object result array.
     */
    protected function normalizeResponse(array $response, string $path = null): array
    {
        $result = ['path' => $path ?: (isset($response['Key']) ? $this->removePathPrefix($response['Key']) : '')];
        $result['dirname'] = Util::dirname($result['path']);

        if (isset($response['LastModified'])) {
            $result['timestamp'] = strtotime($response['LastModified']);
        }

        if (substr($result['path'], -1) === '/') {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');

            return $result;
        }

        $mapped = [];
        foreach (static::$resultMap as $key => $value) {
            if (isset($response[$key])) {
                $mapped[$value] = $response[$key];
            }
        }

        return array_merge($result, $mapped, ['type' => 'file']);
    }
}