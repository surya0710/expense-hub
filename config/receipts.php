<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Receipt Storage Disks
    |--------------------------------------------------------------------------
    |
    | New uploads go to the primary disk (S3 when configured, otherwise local).
    | Each media record stores its disk name, so local and S3 files coexist
    | after migration — reads always use the disk recorded on the file.
    |
    */

    'local_disk' => 'receipts_local',

    's3_disk' => 'receipts_s3',

    'legacy_local_disk' => 'receipts_local',

    'signed_url_ttl_minutes' => (int) env('RECEIPTS_SIGNED_URL_TTL', 15),

    'max_file_size_kb' => (int) env('RECEIPTS_MAX_FILE_SIZE_KB', 5120),

    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],

];
