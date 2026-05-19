<?php

namespace Modules\GoogleWorkspace\Models;

use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;

class GoogleSlides extends Model
{
    use Resourceable;
    protected $table = "google_slides";
    protected $fillable = ['name', 'description', 'is_public', 'drive_id'];
}
