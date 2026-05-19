<?php

namespace Modules\GoogleWorkspace\Models;

use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;

class GoogleSheets extends Model
{
    use Resourceable;
    protected $table = "google_sheets";
    protected $fillable = ['name', 'description', 'is_public', 'drive_id'];
}
