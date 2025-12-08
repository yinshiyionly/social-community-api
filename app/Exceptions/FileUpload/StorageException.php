<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when storage operations fail
 */
class StorageException extends FileUploadException
{
    /**
     * Create a new storage exception instance
     *
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message = 'Storage operation failed', int $code = 500, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}