<?php

namespace Modules\GoogleWorkspace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\Model;

class GoogleToken extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'access_token', 'refresh_token'];
}
