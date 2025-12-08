<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when file deletion from storage fails
 */
class DeleteFailedException extends StorageException
{
    /**
     * Create a new delete failed exception instance
     *
     * @param string $filePath The path of the file that failed to delete
     * @param string $storageDisk The storage disk where deletion was attempted
     * @param string|null $reason Additional reason for failure
     * @param \Exception|null $previous
     */
    public function __construct(
        string $filePath, 
        string $storageDisk, 
        ?string $reason = null, 
        \Exception $previous = null
    ) {
        $message = sprintf(
            'Failed to delete file "%s" from storage disk "%s"',
            $filePath,
            $storageDisk
        );
        
        if ($reason) {
            $message .= ': ' . $reason;
        }
        
        parent::__construct($message, 500, $previous);
    }
}