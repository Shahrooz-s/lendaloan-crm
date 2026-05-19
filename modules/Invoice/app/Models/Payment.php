<?php

namespace Modules\Invoice\Models;

use Modules\Core\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];
    protected $guarded = [];

}
