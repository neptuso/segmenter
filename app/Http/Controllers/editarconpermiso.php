<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class editarconpermiso extends Controller
{
    /**
     * Método para habilitar botones
     * 
     */
    public function editarSi()
    {
        
        //guardar en session varible mostrar controles
        // Store a piece of data in the session...
        
        $verbotones = session('verbotones');

        if ($verbotones) {
            session(['verbotones' => false]);
        } else {
            session(['verbotones' => true]);
        }

        return back();

         
    }

}
