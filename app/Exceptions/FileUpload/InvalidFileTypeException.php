<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when uploaded file type is not allowed
 */
class InvalidFileTypeException extends FileValidationException
{
    /**
     * Create a new invalid file type exception instance
     *
     * @param string $fileType The actual file type/extension
     * @param array $allowedTypes Array of allowed file types
     * @param \Exception|null $previous
     */
    public function __construct(string $fileType, array $allowedTypes = [], \Exception $previous = null)
    {
        $message = sprintf(
            'File type "%s" is not allowed. Allowed types: %s',
            $fileType,
            empty($allowedTypes) ? 'none specified' : implode(', ', $allowedTypes)
        );
        
        parent::__construct($message, 422, $previous);
    }
}