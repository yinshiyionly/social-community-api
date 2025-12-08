<?php

namespace App\Exceptions\FileUpload;

use Exception;

/**
 * Base exception class for file upload operations
 */
class FileUploadException extends Exception
{
    /**
     * Create a new file upload exception instance
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = 'File upload operation failed', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}