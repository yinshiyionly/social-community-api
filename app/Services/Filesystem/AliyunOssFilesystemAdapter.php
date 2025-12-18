<?php

namespace App\Services\Filesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use DateTimeInterface;
use Exception;
use OSS\OssClient;
use OSS\Core\OssException;

class AliyunOssFilesystemAdapter implements AdapterInterface
{
    private OssClient $client;
    private string $bucket;
    private string $prefix;
    private array $config;

    /**
     * @var array
     */
    protected static $resultMap = [
        'Body' => 'contents',
        'Content-Length' => 'size',
        'ContentLength' => 'size',
        'Content-Type' => 'mimetype',
        'ContentType' => 'mimetype',
        'Size' => 'size',
    ];

    public function __construct(OssClient $client, string $bucket, array $config = [])
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
        $requiredKeys = ['key', 'secret', 'bucket', 'endpoint'];
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (empty($this->config[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new ConfigurationException(
                'Missing required Aliyun OSS configuration: ' . implode(', ', $missingKeys)
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
            
            $options = [];

            // Set content type if provided
            if ($contentType = $config->get('ContentType')) {
                $options[OssClient::OSS_CONTENT_TYPE] = $contentType;
            }

            // Set visibility (ACL)
            if ($visibility = $config->get('visibility')) {
                $options[OssClient::OSS_HEADERS] = [
                    'x-oss-object-acl' => $visibility === 'public' ? 'public-read' : 'private'
                ];
            }

            $this->client->putObject($this->bucket, $prefixedPath, $contents, $options);

            return [
                'type' => 'file',
                'path' => $path,
                'dirname' => Util::dirname($path),
                'timestamp' => time(),
            ];
        } catch (OssException $e) {
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
            
            $this->client->copyObject($this->bucket, $prefixedSource, $this->bucket, $prefixedDestination);

            return true;
        } catch (OssException $e) {
            return false;
        }
    }

    public function delete($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $this->client->deleteObject($this->bucket, $prefixedPath);

            return true;
        } catch (OssException $e) {
            return false;
        }
    }

    public function deleteDir($dirname)
    {
        $prefixedPath = $this->applyPathPrefix($dirname);
        
        try {
            $prefix = rtrim($prefixedPath, '/') . '/';
            $nextMarker = '';
            
            while (true) {
                $options = [
                    'prefix' => $prefix,
                    'max-keys' => 1000,
                    'marker' => $nextMarker,
                ];
                
                $result = $this->client->listObjects($this->bucket, $options);
                $objects = $result->getObjectList();
                
                if (empty($objects)) {
                    break;
                }
                
                $keys = array_map(function ($object) {
                    return $object->getKey();
                }, $objects);
                
                $this->client->deleteObjects($this->bucket, $keys);
                
                if (!$result->getIsTruncated()) {
                    break;
                }
                
                $nextMarker = $result->getNextMarker();
            }

            return true;
        } catch (OssException $e) {
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
            $this->client->putObjectAcl($this->bucket, $prefixedPath, $acl);

            return compact('path', 'visibility');
        } catch (OssException $e) {
            return false;
        }
    }

    public function has($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            return $this->client->doesObjectExist($this->bucket, $prefixedPath);
        } catch (OssException $e) {
            return false;
        }
    }

    public function read($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $contents = $this->client->getObject($this->bucket, $prefixedPath);

            return [
                'type' => 'file',
                'path' => $path,
                'contents' => $contents
            ];
        } catch (OssException $e) {
            return false;
        }
    }

    public function readStream($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $contents = $this->client->getObject($this->bucket, $prefixedPath);
            
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $contents);
            rewind($stream);

            return [
                'type' => 'file',
                'path' => $path,
                'stream' => $stream
            ];
        } catch (OssException $e) {
            return false;
        }
    }


    public function listContents($directory = '', $recursive = false)
    {
        $prefixedPath = $this->applyPathPrefix($directory);
        
        try {
            $prefix = $prefixedPath ? rtrim($prefixedPath, '/') . '/' : '';
            $contents = [];
            $nextMarker = '';
            
            while (true) {
                $options = [
                    'prefix' => $prefix,
                    'max-keys' => 1000,
                    'marker' => $nextMarker,
                ];
                
                if (!$recursive) {
                    $options['delimiter'] = '/';
                }
                
                $result = $this->client->listObjects($this->bucket, $options);
                
                // Process files
                $objects = $result->getObjectList();
                if ($objects) {
                    foreach ($objects as $object) {
                        $path = $this->removePathPrefix($object->getKey());
                        
                        // Skip directory markers
                        if (substr($path, -1) === '/') {
                            continue;
                        }
                        
                        $contents[] = [
                            'type' => 'file',
                            'path' => $path,
                            'timestamp' => strtotime($object->getLastModified()),
                            'size' => $object->getSize(),
                        ];
                    }
                }
                
                // Process directories (common prefixes)
                $prefixes = $result->getPrefixList();
                if ($prefixes) {
                    foreach ($prefixes as $prefixInfo) {
                        $path = rtrim($this->removePathPrefix($prefixInfo->getPrefix()), '/');
                        
                        $contents[] = [
                            'type' => 'dir',
                            'path' => $path,
                        ];
                    }
                }
                
                if (!$result->getIsTruncated()) {
                    break;
                }
                
                $nextMarker = $result->getNextMarker();
            }

            return $contents;
        } catch (OssException $e) {
            return [];
        }
    }

    public function getMetadata($path)
    {
        try {
            $prefixedPath = $this->applyPathPrefix($path);
            
            $result = $this->client->getObjectMeta($this->bucket, $prefixedPath);

            return [
                'type' => 'file',
                'path' => $path,
                'timestamp' => isset($result['last-modified']) ? strtotime($result['last-modified']) : null,
                'size' => isset($result['content-length']) ? (int) $result['content-length'] : null,
                'mimetype' => $result['content-type'] ?? null,
            ];
        } catch (OssException $e) {
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
            
            $acl = $this->client->getObjectAcl($this->bucket, $prefixedPath);

            $visibility = AdapterInterface::VISIBILITY_PRIVATE;
            if ($acl === 'public-read' || $acl === 'public-read-write') {
                $visibility = AdapterInterface::VISIBILITY_PUBLIC;
            }

            return compact('path', 'visibility');
        } catch (OssException $e) {
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
            
            $timeout = $expiresAt->getTimestamp() - time();
            
            $signedUrl = $this->client->signUrl($this->bucket, $prefixedPath, $timeout, OssClient::OSS_HTTP_GET, $options);

            return $signedUrl;
        } catch (OssException $e) {
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

    /**
     * Get the OssClient instance
     */
    public function getClient(): OssClient
    {
        return $this->client;
    }

    /**
     * Get the bucket name
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }
}
