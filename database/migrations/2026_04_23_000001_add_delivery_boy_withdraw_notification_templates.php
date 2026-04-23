<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDeliveryBoyWithdrawNotificationTemplates extends Migration
{
    public function up()
    {
        // ── Email template : admin notifié quand livreur soumet une demande ──────
        if (!DB::table('email_templates')->where('identifier', 'delivery_boy_withdraw_request_to_admin')->exists()) {
            DB::table('email_templates')->insert([
                'receiver'                 => 'admin',
                'identifier'               => 'delivery_boy_withdraw_request_to_admin',
                'email_type'               => 'admin',
                'subject'                  => 'Nouvelle demande de retrait – [[delivery_boy_name]]',
                'default_text'             => '
<p>Bonjour [[admin_name]],</p>
<p>Le livreur <strong>[[delivery_boy_name]]</strong> ([[delivery_boy_phone]]) a soumis une nouvelle demande de retrait.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant</td><td style="border:1px solid #eee;padding:8px"><strong>[[amount]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Mode de paiement</td><td style="border:1px solid #eee;padding:8px">[[payment_method]]</td></tr>
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

        // ── SMS templates : livreur notifié par l'admin ──────────────────────────
        // La table sms_templates n'existe que si l'addon otp_system est installé
        if (!Schema::hasTable('sms_templates')) {
            return;
        }

        $smsTemplates = [
            [
                'identifier'  => 'delivery_boy_withdraw_approved',
                'sms_body'    => 'Bonjour, votre demande de retrait de [[amount]] a été approuvée. Le paiement sera effectué prochainement. – [[site_name]]',
                'template_id' => null,
                'status'      => 1,
            ],
            [
                'identifier'  => 'delivery_boy_withdraw_paid',
                'sms_body'    => 'Bonjour, votre retrait de [[amount]] a été payé. Vérifiez votre compte [[payment_method]]. – [[site_name]]',
                'template_id' => null,
                'status'      => 1,
            ],
            [
                'identifier'  => 'delivery_boy_withdraw_rejected',
                'sms_body'    => 'Bonjour, votre demande de retrait de [[amount]] a été refusée. Motif : [[reason]]. – [[site_name]]',
                'template_id' => null,
                'status'      => 1,
            ],
        ];

        foreach ($smsTemplates as $template) {
            if (!DB::table('sms_templates')->where('identifier', $template['identifier'])->exists()) {
                DB::table('sms_templates')->insert(array_merge($template, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down()
    {
        DB::table('email_templates')
            ->where('identifier', 'delivery_boy_withdraw_request_to_admin')
            ->delete();

        if (Schema::hasTable('sms_templates')) {
            DB::table('sms_templates')->whereIn('identifier', [
                'delivery_boy_withdraw_approved',
                'delivery_boy_withdraw_paid',
                'delivery_boy_withdraw_rejected',
            ])->delete();
        }
    }
}
