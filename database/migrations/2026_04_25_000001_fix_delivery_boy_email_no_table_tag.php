<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixDeliveryBoyEmailNoTableTag extends Migration
{
    public function up()
    {
        // emails/index.blade.php enveloppe le contenu dans <p>...</p>.
        // Un <table> (élément bloc) à l'intérieur d'un <p> est du HTML invalide :
        // le client email ferme le <p> avant le tableau → le texte qui suit le tableau
        // se retrouve orphelin et se superpose visuellement au contenu précédent.
        // Solution : n'utiliser que des éléments inline (<strong>, <br>) dans ce template.
        $body = 'Bonjour [[admin_name]],<br><br>'
            . 'Le livreur <strong>[[delivery_boy_name]]</strong> ([[delivery_boy_phone]]) a soumis une nouvelle demande de retrait.<br><br>'
            . '<strong>Montant :</strong> [[amount]]<br>'
            . '<strong>Mode de paiement :</strong> [[payment_method]]<br>'
            . '<strong>Date :</strong> [[date]]<br><br>'
            . 'Connectez-vous au panneau d\'administration pour traiter cette demande.<br><br>'
            . 'Cordialement,<br>'
            . '<strong>[[store_name]]</strong>';

        DB::table('email_templates')
            ->where('identifier', 'delivery_boy_withdraw_request_to_admin')
            ->update([
                'default_text' => $body,
                'updated_at'   => now(),
            ]);
    }

    public function down()
    {
        // pas de rollback nécessaire
    }
}
