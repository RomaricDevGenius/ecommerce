<?php

namespace App\Http\Middleware;

use App;
use Config;
use Closure;
use Session;
use Carbon\Carbon;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Session::has('locale')){
            $locale = Session::get('locale');
        }
        else{
            // Langue d'affichage par défaut pour les nouveaux visiteurs (1ère visite).
            // Découplée de DEFAULT_LANGUAGE, qui reste la langue de référence du CONTENU
            // (produits, catégories...). Par défaut : français. Surchargeable via .env.
            $locale = env('FRONTEND_DEFAULT_LANGUAGE', 'fr');
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        $langcode = Session::has('langcode') ? Session::get('langcode') : 'en';
        Carbon::setLocale($langcode);

        return $next($request);
    }
}
