<?php

namespace Modules\Sms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\Model;
use Modules\Users\Models\User;


class Schedules extends Model
{
    use HasFactory;

    protected $table = "schedules";
    protected $fillable = [
        'created_by',
        'data',
        'scheduled_at'
    ];

    protected static function booted(): void
    {
        static::creating(function (Schedules $model) {
            $model->created_by =  auth()->id();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, "created_by");
    }
}
