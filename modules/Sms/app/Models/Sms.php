<?php

namespace Modules\Sms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Activities\Models\ActivityType;
use Modules\Core\Models\Model;
use Modules\Core\Contracts\Resources\Resourceable as ResourceableContract;
use Modules\Core\Resource\Resourceable;
use Modules\Users\Models\User;


class Sms extends Model implements ResourceableContract
{
    use HasFactory, Resourceable;

    protected $table = "sms_messages";
    protected $fillable = [
        'user_id',
        'activity_type_id',
        'contact_id',
        
        'message',
        'status',

        'sid',
        'direction',
        'from',
        'to',

    ];

    protected static function booted(): void
    {
        static::creating(function (Sms $model) {
            $model->user_id =  auth()->id();
        });

        static::deleting(function (Sms $model) {
            $model->purge();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }
}
