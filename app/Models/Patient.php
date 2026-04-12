<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'personal_data_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    public function personalData(): BelongsTo
    {
        return $this->belongsTo(PersonalData::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    public function clinicalNotes(): HasManyThrough
    {
        return $this->hasManyThrough(ClinicalNote::class, Appointment::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    // ==================== ACCESSORS ====================

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fullName,
        );
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->personalData
                ? trim($this->personalData->first_name.' '.$this->personalData->last_name)
                : 'Sin nombre'
        );
    }

    public function email(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->personalData?->contact?->email
        );
    }

    public function phone(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->personalData?->contact?->phone
        );
    }

    public function dni(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->personalData?->dni
        );
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->personalData?->birth_date
                ? $this->personalData->birth_date->age
                : null
        );
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('personalData', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('dni', 'like', "%{$search}%");
        });
    }
}
