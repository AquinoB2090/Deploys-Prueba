<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'Estudiantes';

    protected $primaryKey = 'Carnet';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'Carnet',
        'Nombre',
        'Correo',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleMision::class, 'Carnet', 'Carnet');
    }

    public function misiones()
    {
        return $this->belongsToMany(Mision::class, 'EstudianteMisiones', 'Carnet', 'MisionID', 'Carnet', 'MisionID')
            ->withPivot('Estado');
    }
}
