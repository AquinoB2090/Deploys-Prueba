<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleMision extends Model
{
    protected $table = 'DetalleMisiones';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'Carnet',
        'MisionID',
        'Estado',
    ];

    protected $casts = [
        'MisionID' => 'integer',
        'Estado' => 'boolean',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'Carnet', 'Carnet');
    }

    public function mision()
    {
        return $this->belongsTo(Mision::class, 'MisionID', 'MisionID');
    }
}
