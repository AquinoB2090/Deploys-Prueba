<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mision extends Model
{
    protected $table = 'Misiones';

    protected $primaryKey = 'MisionID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'MisionID',
        'Nombre',
    ];

    protected $casts = [
        'MisionID' => 'integer',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleMision::class, 'MisionID', 'MisionID');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'DetalleMisiones', 'MisionID', 'Carnet', 'MisionID', 'Carnet')
            ->withPivot('Estado');
    }
}
