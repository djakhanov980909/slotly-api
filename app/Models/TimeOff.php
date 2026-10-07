<?php

namespace App\Models;

use Database\Factories\TimeOffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $specialist_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $reason
 */
class TimeOff extends Model
{
    /** @use HasFactory<TimeOffFactory> */
    use HasFactory;

    protected $fillable = ['specialist_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function specialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specialist_id');
    }
}
