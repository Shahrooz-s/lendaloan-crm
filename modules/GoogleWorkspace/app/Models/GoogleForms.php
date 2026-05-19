<?php

namespace Modules\GoogleWorkspace\Models;

use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;


class GoogleForms extends Model
{
    use Resourceable;
    protected $table = "google_forms";
    protected $fillable = ['name', 'description', 'is_public', 'drive_id'];
}
