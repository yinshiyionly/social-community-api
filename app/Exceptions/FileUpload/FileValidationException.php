<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when file validation fails
 */
class FileValidationException extends FileUploadException
{
    /**
     * Create a new file validation exception instance
     *
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message = 'File validation failed', int $code = 422, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}