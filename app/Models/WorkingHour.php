<?php

namespace App\Models;

use Database\Factories\WorkingHourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $specialist_id
 * @property int $weekday
 * @property string $start_time
 * @property string $end_time
 */
class WorkingHour extends Model
{
    /** @use HasFactory<WorkingHourFactory> */
    use HasFactory;

    protected $fillable = ['specialist_id', 'weekday', 'start_time', 'end_time'];

    /** @return BelongsTo<User, $this> */
    public function specialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specialist_id');
    }
}
