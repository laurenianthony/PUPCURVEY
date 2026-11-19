<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'target_programs',
        'target_year_levels',
        'schema',
        'target_respondents',
        'deadline_at',
        'is_closed',
        'shuffle_questions',
        'collect_respondent_info',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'target_programs' => 'array',
            'target_year_levels' => 'array',
            'schema' => 'array',
            'target_respondents' => 'integer',
            'deadline_at' => 'datetime',
            'is_closed' => 'boolean',
            'shuffle_questions' => 'boolean',
            'collect_respondent_info' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function isAcceptingResponses(): bool
    {
        if ($this->status !== 'approved' || $this->is_closed) {
            return false;
        }

        if ($this->deadline_at !== null && $this->deadline_at->isPast()) {
            return false;
        }

        if ($this->target_respondents !== null) {
            $responseCount = $this->responses_count ?? $this->responses()->count();

            if ($responseCount >= $this->target_respondents) {
                return false;
            }
        }

        return true;
    }

    public function isEligibleUser(User $user): bool
    {
        $targetPrograms = $this->target_programs ?? [];
        $targetYearLevels = $this->target_year_levels ?? [];

        $programMatches = in_array('ALL', $targetPrograms, true)
            || in_array($user->program, $targetPrograms, true);

        $yearLevelMatches = in_array('ALL', $targetYearLevels, true)
            || in_array((int) $user->year_level, array_map('intval', $targetYearLevels), true);

        return $programMatches && $yearLevelMatches;
    }
}
