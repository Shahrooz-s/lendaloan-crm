<?php

namespace Modules\GoogleWorkspace\Models;

use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;


class GoogleDrives extends Model
{
    use Resourceable;
    protected $table = 'google_drives';
    protected $fillable = ['drive_id', 'name', 'description' ,'mime_type','is_public'];
}