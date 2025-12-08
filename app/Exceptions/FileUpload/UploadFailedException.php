<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when file upload to storage fails
 */
class UploadFailedException extends StorageException
{
    /**
     * Create a new upload failed exception instance
     *
     * @param string $fileName The name of the file that failed to upload
     * @param string $storageDisk The storage disk where upload was attempted
     * @param string|null $reason Additional reason for failure
     * @param \Exception|null $previous
     */
    public function __construct(
        string $fileName, 
        string $storageDisk, 
        ?string $reason = null, 
        \Exception $previous = null
    ) {
        $message = sprintf(
            'Failed to upload file "%s" to storage disk "%s"',
            $fileName,
            $storageDisk
        );
        
        if ($reason) {
            $message .= ': ' . $reason;
        }
        
        parent::__construct($message, 500, $previous);
    }
}