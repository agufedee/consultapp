<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalNote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'observations',
        'instructions',
        'diagnosis',
    ];

    // Relaciones
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
