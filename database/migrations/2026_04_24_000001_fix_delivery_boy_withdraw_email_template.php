<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixDeliveryBoyWithdrawEmailTemplate extends Migration
{
    public function up()
    {
        // La blade emails/index.blade.php enveloppe déjà le contenu dans <p>.
        // Utiliser des <p> dans default_text crée des <p> imbriqués → rendu cassé.
        // On utilise uniquement <br> pour les sauts de ligne.
        $body = 'Bonjour [[admin_name]],<br><br>'
            . 'Le livreur <strong>[[delivery_boy_name]]</strong> ([[delivery_boy_phone]]) a soumis une nouvelle demande de retrait.<br><br>'
            . '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">'
            . '<tr><td style="border:1px solid #e5e7eb;color:#6b7280">Montant</td><td style="border:1px solid #e5e7eb"><strong>[[amount]]</strong></td></tr>'
            . '<tr><td style="border:1px solid #e5e7eb;color:#6b7280">Mode de paiement</td><td style="border:1px solid #e5e7eb">[[payment_method]]</td></tr>'
            . '<tr><td style="border:1px solid #e5e7eb;color:#6b7280">Date</td><td style="border:1px solid #e5e7eb">[[date]]</td></tr>'
            . '</table><br>'
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
        // Pas de rollback nécessaire pour un fix de texte
    }
}
