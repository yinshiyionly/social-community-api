<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when storage credentials are invalid
 */
class InvalidCredentialsException extends ConfigurationException
{
    /**
     * Create a new invalid credentials exception instance
     *
     * @param string $storageDisk The storage disk with invalid credentials
     * @param string|null $reason Additional reason for credential failure
     * @param \Exception|null $previous
     */
    public function __construct(string $storageDisk, ?string $reason = null, \Exception $previous = null)
    {
        $message = sprintf(
            'Invalid credentials for storage disk "%s"',
            $storageDisk
        );
        
        if ($reason) {
            $message .= ': ' . $reason;
        }
        
        parent::__construct($message, 401, $previous);
    }
}