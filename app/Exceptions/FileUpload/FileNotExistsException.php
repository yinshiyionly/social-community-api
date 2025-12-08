<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when attempting to access a file that doesn't exist in storage
 */
class FileNotExistsException extends StorageException
{
    /**
     * Create a new file not exists exception instance
     *
     * @param string $filePath The path of the file that doesn't exist
     * @param string $storageDisk The storage disk where file was expected
     * @param \Exception|null $previous
     */
    public function __construct(string $filePath, string $storageDisk, \Exception $previous = null)
    {
        $message = sprintf(
            'File "%s" does not exist in storage disk "%s"',
            $filePath,
            $storageDisk
        );
        
        parent::__construct($message, 404, $previous);
    }
}