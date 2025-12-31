<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "volcengine"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        'volcengine' => [
            'driver' => 'volcengine',
            'key' => env('VOLCENGINE_ACCESS_KEY'),
            'secret' => env('VOLCENGINE_SECRET_KEY'),
            'region' => env('VOLCENGINE_REGION', 'cn-beijing'),
            'bucket' => env('VOLCENGINE_BUCKET'),
            'endpoint' => env('VOLCENGINE_ENDPOINT'),
            'internal_endpoint' => env('VOLCENGINE_INTERNAL_ENDPOINT'), // 内网端点，用于上传操作
            'url' => env('VOLCENGINE_URL'), // CDN domain (without protocol)
            'schema' => env('VOLCENGINE_SCHEMA', 'https'),
            'visibility' => env('VOLCENGINE_VISIBILITY', 'public'),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure file upload limits and allowed file types.
    | max_upload_size: Maximum file size in KB
    | allowed_mimes: Array of allowed file extensions
    |
    */

    'max_upload_size' => env('MAX_UPLOAD_SIZE', 10240), // KB

    'allowed_mimes' => env('ALLOWED_MIMES') ?
        explode(',', env('ALLOWED_MIMES')) : [
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico',
            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
            // Text files
            'txt', 'csv', 'rtf', 'md',
            // Archives
            'zip', 'rar', '7z', 'tar', 'gz',
            // Audio
            'mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a',
            // Video
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv',
        ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security Configuration
    |--------------------------------------------------------------------------
    |
    | Additional security settings for file uploads.
    |
    */

    'upload_security' => [
        'check_mime_type' => env('UPLOAD_CHECK_MIME_TYPE', true),
        'generate_unique_names' => env('UPLOAD_GENERATE_UNIQUE_NAMES', true),
        'sanitize_filenames' => env('UPLOAD_SANITIZE_FILENAMES', true),
        'max_filename_length' => env('UPLOAD_MAX_FILENAME_LENGTH', 255),
    ],

];
