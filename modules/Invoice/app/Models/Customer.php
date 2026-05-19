<?php

namespace Modules\Invoice\Models;

use Illuminate\Notifications\Notifiable;
use Modules\Core\Contracts\HasNotificationsSettings;
use Modules\Core\Models\Model;

class Customer extends Model implements HasNotificationsSettings{
    use Notifiable;

    protected $table = 'contacts';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [
        'created_by',
        'created_at',
        'updated_at',
        'owner_assigned_date',
        'next_activity_id',
        'uuid',
    ];

    public function getNotificationsPreferences(string $key): array
    {
        return [
            "mail" => true,
            "database" => false,
            "broadcast" => false
        ];
    }
}
