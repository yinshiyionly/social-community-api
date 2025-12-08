<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when configuration is invalid or missing
 */
class ConfigurationException extends FileUploadException
{
    /**
     * Create a new configuration exception instance
     *
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message = 'Configuration error', int $code = 500, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}