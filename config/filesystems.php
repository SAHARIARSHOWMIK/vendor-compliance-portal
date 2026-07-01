<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
         * Dedicated disk for vendor-uploaded compliance documents.
         * Deliberately NOT on the 'public' disk - these are sensitive
         * business documents (tax certificates, bank letters, etc.) and
         * must only ever be served through an authenticated, authorized
         * controller action (see VendorDocumentController::download in
         * Phase 4), never via a direct public URL.
         *
         * For local/demo use this writes to storage/app/private/vendor-documents.
         * For production, swap the driver to 's3' (or any Flysystem
         * adapter) - no application code changes needed elsewhere, since
         * all document storage/retrieval goes through this disk name.
         */
        'vendor_documents' => [
            'driver' => env('VENDOR_DOCUMENTS_DISK_DRIVER', 'local'),
            'root' => storage_path('app/private/vendor-documents'),
            'throw' => true,
            // S3 credentials, only used when VENDOR_DOCUMENTS_DISK_DRIVER=s3:
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'visibility' => 'private',
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
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
