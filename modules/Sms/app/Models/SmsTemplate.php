<?php

namespace Modules\Sms\Models;

use Modules\Core\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Resource\Resourceable;

class SmsTemplate extends Model
{
    use HasFactory, Resourceable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

}
