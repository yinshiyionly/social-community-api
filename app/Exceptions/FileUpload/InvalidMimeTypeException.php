<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when file MIME type doesn't match the file extension
 */
class InvalidMimeTypeException extends FileValidationException
{
    /**
     * Create a new invalid MIME type exception instance
     *
     * @param string $actualMimeType The actual MIME type detected
     * @param string $expectedMimeType The expected MIME type based on extension
     * @param string $fileExtension The file extension
     * @param \Exception|null $previous
     */
    public function __construct(
        string $actualMimeType, 
        string $expectedMimeType, 
        string $fileExtension, 
        \Exception $previous = null
    ) {
        $message = sprintf(
            'File MIME type "%s" does not match expected type "%s" for extension ".%s"',
            $actualMimeType,
            $expectedMimeType,
            $fileExtension
        );
        
        parent::__construct($message, 422, $previous);
    }
}