<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = ['nombre', 'precio', 'descripcion'];
    protected $casts = ['precio' => 'decimal:2'];

    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
