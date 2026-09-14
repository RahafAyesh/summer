<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'category',
        'description',
        'frequency',
        'next_due_date',
        'end_date',
        'is_active',
        'last_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'frequency' => RecurrenceFrequency::class,
            'next_due_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'last_generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDue(Builder $query, Carbon $date): Builder
    {
        return $query->where('next_due_date', '<=', $date);
    }
}
