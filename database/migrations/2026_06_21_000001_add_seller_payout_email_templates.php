<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSellerPayoutEmailTemplates extends Migration
{
    public function up()
    {
        // 1. Admin notifié quand le vendeur soumet une demande de retrait
        if (!DB::table('email_templates')->where('identifier', 'seller_payout_request_email_to_admin')->exists()) {
            DB::table('email_templates')->insert([
                'receiver'                 => 'admin',
                'identifier'               => 'seller_payout_request_email_to_admin',
                'email_type'               => 'admin',
                'subject'                  => 'Nouvelle demande de retrait – [[shop_name]]',
                'default_text'             => '
<p>Bonjour [[admin_name]],</p>
<p>La boutique <strong>[[shop_name]]</strong> ([[shop_email]]) a soumis une nouvelle demande de retrait.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant demandé</td><td style="border:1px solid #eee;padding:8px"><strong>[[amount]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Connectez-vous au panneau d\'administration pour traiter cette demande.</p>
<p>Cordialement,<br>[[store_name]]</p>
',
                'status'                   => 1,
                'is_status_changeable'     => 1,
                'is_dafault_text_editable' => 1,
                'addon'                    => null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }

        // 2. Vendeur confirmé que sa demande de retrait a bien été reçue
        if (!DB::table('email_templates')->where('identifier', 'seller_payout_request_email_to_seller')->exists()) {
            DB::table('email_templates')->insert([
                'receiver'                 => 'seller',
                'identifier'               => 'seller_payout_request_email_to_seller',
                'email_type'               => 'seller',
                'subject'                  => 'Votre demande de retrait a été reçue – [[store_name]]',
                'default_text'             => '
<p>Bonjour,</p>
<p>Nous avons bien reçu votre demande de retrait pour la boutique <strong>[[shop_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant demandé</td><td style="border:1px solid #eee;padding:8px"><strong>[[amount]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Votre demande est en cours de traitement. Vous recevrez un e-mail dès que le paiement est effectué.</p>
<p>Cordialement,<br>[[store_name]]</p>
',
                'status'                   => 1,
                'is_status_changeable'     => 1,
                'is_dafault_text_editable' => 1,
                'addon'                    => null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }

        // 3. Admin confirmation quand il paie un vendeur
        if (!DB::table('email_templates')->where('identifier', 'seller_payout_email_to_admin')->exists()) {
            DB::table('email_templates')->insert([
                'receiver'                 => 'admin',
                'identifier'               => 'seller_payout_email_to_admin',
                'email_type'               => 'admin',
                'subject'                  => 'Paiement vendeur effectué – [[shop_name]]',
                'default_text'             => '
<p>Bonjour [[admin_name]],</p>
<p>Un paiement a été effectué avec succès pour la boutique <strong>[[shop_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Boutique</td><td style="border:1px solid #eee;padding:8px">[[shop_name]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">E-mail vendeur</td><td style="border:1px solid #eee;padding:8px">[[shop_email]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant payé</td><td style="border:1px solid #eee;padding:8px"><strong>[[amount]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Mode de paiement</td><td style="border:1px solid #eee;padding:8px">[[payment_method]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Ce message est une confirmation automatique.</p>
<p>Cordialement,<br>[[store_name]]</p>
',
                'status'                   => 1,
                'is_status_changeable'     => 1,
                'is_dafault_text_editable' => 1,
                'addon'                    => null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }

        // 4. Vendeur notifié que l'admin l'a payé
        if (!DB::table('email_templates')->where('identifier', 'seller_payout_email_to_seller')->exists()) {
            DB::table('email_templates')->insert([
                'receiver'                 => 'seller',
                'identifier'               => 'seller_payout_email_to_seller',
                'email_type'               => 'seller',
                'subject'                  => 'Votre paiement a été effectué – [[store_name]]',
                'default_text'             => '
<p>Bonjour,</p>
<p>Bonne nouvelle ! Votre paiement pour la boutique <strong>[[shop_name]]</strong> a été effectué avec succès.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant reçu</td><td style="border:1px solid #eee;padding:8px"><strong>[[amount]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Mode de paiement</td><td style="border:1px solid #eee;padding:8px">[[payment_method]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Si vous avez des questions, contactez-nous à [[admin_email]].</p>
<p>Cordialement,<br>[[store_name]]</p>
',
                'status'                   => 1,
                'is_status_changeable'     => 1,
                'is_dafault_text_editable' => 1,
                'addon'                    => null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('email_templates')->whereIn('identifier', [
            'seller_payout_request_email_to_admin',
            'seller_payout_request_email_to_seller',
            'seller_payout_email_to_admin',
            'seller_payout_email_to_seller',
        ])->delete();
    }
}
