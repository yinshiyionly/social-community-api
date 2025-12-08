<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when required configuration is missing
 */
class MissingConfigException extends ConfigurationException
{
    /**
     * Create a new missing config exception instance
     *
     * @param string $configKey The missing configuration key
     * @param string $storageDisk The storage disk missing configuration
     * @param \Exception|null $previous
     */
    public function __construct(string $configKey, string $storageDisk, \Exception $previous = null)
    {
        $message = sprintf(
            'Missing required configuration "%s" for storage disk "%s"',
            $configKey,
            $storageDisk
        );
        
        parent::__construct($message, 500, $previous);
    }
}