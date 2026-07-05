<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class TranslateEmailTemplatesToFrench extends Migration
{
    private function row(string $subject, string $body): array
    {
        return ['subject' => $subject, 'default_text' => $body, 'updated_at' => now()];
    }

    private function tpl(string $body): string
    {
        // Wrapper commun à tous les e-mails
        return '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;line-height:1.6">'
            . $body
            . '</div>';
    }

    public function up()
    {
        $templates = [

            // ── INSCRIPTIONS ────────────────────────────────────────────────────

            'customer_reg_email_to_admin' => $this->row(
                'Nouvelle inscription client - [[customer_name]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>Un nouveau client vient de s\'inscrire sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Nom</td><td style="border:1px solid #eee;padding:8px"><strong>[[customer_name]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">E-mail / Téléphone</td><td style="border:1px solid #eee;padding:8px">[[email/phone]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date d\'inscription</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Connectez-vous au panneau d\'administration pour consulter les détails.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'registration_email_to_customer' => $this->row(
                'Bienvenue sur [[store_name]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Nous sommes ravis de vous accueillir sur <strong>[[store_name]]</strong> !</p>
<p>Votre compte a été créé avec succès. Vous pouvez dès maintenant découvrir nos produits et passer vos commandes.</p>
<p style="margin-top:16px">Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'registration_from_system_email_to_customer' => $this->row(
                'Bienvenue sur [[store_name]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Un compte client a été créé pour vous sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">E-mail</td><td style="border:1px solid #eee;padding:8px">[[email]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Mot de passe temporaire</td><td style="border:1px solid #eee;padding:8px">[[password]]</td></tr>
</table>
<p style="margin-top:16px">Veuillez vous connecter et modifier votre mot de passe dès que possible.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'seller_reg_email_to_admin' => $this->row(
                'Nouvelle inscription vendeur - [[shop_name]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>Un nouveau vendeur vient de s\'inscrire sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Boutique</td><td style="border:1px solid #eee;padding:8px"><strong>[[shop_name]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">E-mail</td><td style="border:1px solid #eee;padding:8px">[[shop_email]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date d\'inscription</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Connectez-vous au panneau d\'administration pour approuver ou refuser cette inscription.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'registration_email_to_seller' => $this->row(
                'Bienvenue sur [[store_name]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Félicitations ! Votre compte vendeur a été créé avec succès sur <strong>[[store_name]]</strong>.</p>
<p>Votre boutique <strong>[[shop_name]]</strong> sera visible après validation par notre équipe.</p>
<p style="margin-top:16px">Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'registration_from_system_email_to_seller' => $this->row(
                'Bienvenue sur [[store_name]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Un compte vendeur a été créé pour votre boutique <strong>[[shop_name]]</strong> sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">E-mail</td><td style="border:1px solid #eee;padding:8px">[[email]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Mot de passe temporaire</td><td style="border:1px solid #eee;padding:8px">[[password]]</td></tr>
</table>
<p style="margin-top:16px">Veuillez vous connecter et modifier votre mot de passe dès que possible.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'deliveryboy_reg_email_to_admin' => $this->row(
                'Nouvelle inscription livreur - [[store_name]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>Un nouveau livreur vient de s\'inscrire sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Nom</td><td style="border:1px solid #eee;padding:8px"><strong>[[delivery_boy_name]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Téléphone</td><td style="border:1px solid #eee;padding:8px">[[delivery_boy_phone]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Connectez-vous au panneau d\'administration pour valider cette inscription.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'registration_from_system_email_to_deliveryboy' => $this->row(
                'Bienvenue dans l\'équipe de livraison [[store_name]] !',
                $this->tpl('
<p>Bonjour [[delivery_boy_name]],</p>
<p>Bienvenue dans l\'équipe de livraison de <strong>[[store_name]]</strong> !</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">E-mail</td><td style="border:1px solid #eee;padding:8px">[[email]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Mot de passe temporaire</td><td style="border:1px solid #eee;padding:8px">[[password]]</td></tr>
</table>
<p style="margin-top:16px">Veuillez vous connecter à l\'application livreur et modifier votre mot de passe.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            // ── VÉRIFICATION D'E-MAIL ───────────────────────────────────────────

            'email_verification_seller' => $this->row(
                'Vérifiez votre e-mail pour activer votre compte vendeur sur [[store_name]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse e-mail et activer votre compte vendeur sur <strong>[[store_name]]</strong>.</p>
<p style="text-align:center;margin:24px 0">
  <a href="[[verification_link]]" style="background:#e53935;color:#fff;padding:12px 32px;border-radius:4px;text-decoration:none;font-weight:bold">Vérifier mon e-mail</a>
</p>
<p>Si vous n\'avez pas créé ce compte, ignorez cet e-mail.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'email_verification_customer' => $this->row(
                'Confirmez votre e-mail pour finaliser votre inscription sur [[store_name]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Merci de vous être inscrit sur <strong>[[store_name]]</strong>. Veuillez confirmer votre adresse e-mail en cliquant sur le bouton ci-dessous.</p>
<p style="text-align:center;margin:24px 0">
  <a href="[[verification_link]]" style="background:#e53935;color:#fff;padding:12px 32px;border-radius:4px;text-decoration:none;font-weight:bold">Confirmer mon e-mail</a>
</p>
<p>Si vous n\'avez pas créé ce compte, ignorez cet e-mail.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'email_verification_for_registration_seller' => $this->row(
                'Vérification d\'e-mail sur [[store_name]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Votre code de vérification pour finaliser votre inscription sur <strong>[[store_name]]</strong> est :</p>
<p style="font-size:28px;font-weight:bold;text-align:center;letter-spacing:6px;margin:20px 0;color:#e53935">[[code]]</p>
<p>Ce code est valable 10 minutes. Ne le partagez avec personne.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'email_verification_for_registration_customer' => $this->row(
                'Vérification d\'e-mail sur [[store_name]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Votre code de vérification pour finaliser votre inscription sur <strong>[[store_name]]</strong> est :</p>
<p style="font-size:28px;font-weight:bold;text-align:center;letter-spacing:6px;margin:20px 0;color:#e53935">[[code]]</p>
<p>Ce code est valable 10 minutes. Ne le partagez avec personne.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'email_update_verification_seller' => $this->row(
                'Vérifiez votre nouvelle adresse e-mail - [[store_name]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Vous avez demandé la mise à jour de votre adresse e-mail sur <strong>[[store_name]]</strong>. Veuillez vérifier votre nouvelle adresse en cliquant ci-dessous.</p>
<p style="text-align:center;margin:24px 0">
  <a href="[[verification_link]]" style="background:#e53935;color:#fff;padding:12px 32px;border-radius:4px;text-decoration:none;font-weight:bold">Vérifier ma nouvelle adresse</a>
</p>
<p>Si vous n\'avez pas effectué cette demande, ignorez cet e-mail.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'email_update_verification_customer' => $this->row(
                'Confirmez votre nouvelle adresse e-mail - [[store_name]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Vous avez demandé la mise à jour de votre adresse e-mail sur <strong>[[store_name]]</strong>. Veuillez confirmer votre nouvelle adresse en cliquant ci-dessous.</p>
<p style="text-align:center;margin:24px 0">
  <a href="[[verification_link]]" style="background:#e53935;color:#fff;padding:12px 32px;border-radius:4px;text-decoration:none;font-weight:bold">Confirmer ma nouvelle adresse</a>
</p>
<p>Si vous n\'avez pas effectué cette demande, ignorez cet e-mail.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'change_email_verification_code_customer' => $this->row(
                'Code de vérification pour la mise à jour de votre e-mail - [[store_name]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Votre code de vérification pour la mise à jour de votre adresse e-mail sur <strong>[[store_name]]</strong> est :</p>
<p style="font-size:28px;font-weight:bold;text-align:center;letter-spacing:6px;margin:20px 0;color:#e53935">[[code]]</p>
<p>Ce code est valable 10 minutes. Ne le partagez avec personne.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'change_email_verification_code_seller' => $this->row(
                'Code de vérification pour la mise à jour de votre e-mail - [[store_name]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Votre code de vérification pour la mise à jour de votre adresse e-mail sur <strong>[[store_name]]</strong> est :</p>
<p style="font-size:28px;font-weight:bold;text-align:center;letter-spacing:6px;margin:20px 0;color:#e53935">[[code]]</p>
<p>Ce code est valable 10 minutes. Ne le partagez avec personne.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            // ── MOT DE PASSE ────────────────────────────────────────────────────

            'password_reset_email_to_all' => $this->row(
                'Réinitialisation du mot de passe - [[store_name]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Vous avez demandé la réinitialisation de votre mot de passe sur <strong>[[store_name]]</strong>.</p>
<p style="text-align:center;margin:24px 0">
  <a href="[[reset_link]]" style="background:#e53935;color:#fff;padding:12px 32px;border-radius:4px;text-decoration:none;font-weight:bold">Réinitialiser mon mot de passe</a>
</p>
<p>Ce lien expire dans 60 minutes. Si vous n\'avez pas effectué cette demande, ignorez cet e-mail.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            // ── COMMANDES ───────────────────────────────────────────────────────

            'order_placed_email_to_admin' => $this->row(
                'Commande passée - [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>Une nouvelle commande a été passée sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">N° commande</td><td style="border:1px solid #eee;padding:8px"><strong>[[order_code]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant</td><td style="border:1px solid #eee;padding:8px">[[order_amount]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[order_date]]</td></tr>
</table>
<p style="margin-top:16px">Connectez-vous au panneau d\'administration pour traiter cette commande.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_placed_email_to_seller' => $this->row(
                'Nouvelle commande [[order_code]] reçue !',
                $this->tpl('
<p>Bonjour,</p>
<p>Bonne nouvelle ! Une nouvelle commande a été passée dans votre boutique sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">N° commande</td><td style="border:1px solid #eee;padding:8px"><strong>[[order_code]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant</td><td style="border:1px solid #eee;padding:8px">[[order_amount]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[order_date]]</td></tr>
</table>
<p style="margin-top:16px">Connectez-vous à votre espace vendeur pour préparer cette commande.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_placed_email_to_customer' => $this->row(
                'Votre commande [[order_code]] a été passée avec succès !',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Merci pour votre commande sur <strong>[[store_name]]</strong> ! Nous l\'avons bien reçue et elle est en cours de traitement.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">N° commande</td><td style="border:1px solid #eee;padding:8px"><strong>[[order_code]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant total</td><td style="border:1px solid #eee;padding:8px">[[order_amount]]</td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date de commande</td><td style="border:1px solid #eee;padding:8px">[[order_date]]</td></tr>
</table>
<p style="margin-top:16px">Nous vous tiendrons informé(e) de l\'avancement de votre commande par e-mail.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_confirmed_email_to_admin' => $this->row(
                'Commande confirmée - [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La commande <strong>[[order_code]]</strong> a été confirmée sur <strong>[[store_name]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_confirmed_email_to_seller' => $this->row(
                'Commande confirmée - [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>La commande <strong>[[order_code]]</strong> dans votre boutique a été confirmée. Veuillez préparer la livraison.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_confirmed_email_to_customer' => $this->row(
                'Votre commande [[order_code]] a été confirmée !',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Votre commande <strong>[[order_code]]</strong> sur <strong>[[store_name]]</strong> a été confirmée et est en cours de préparation.</p>
<p>Nous vous informerons dès qu\'elle sera expédiée.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_picked_up_email_to_admin' => $this->row(
                'Commande ramassée - [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La commande <strong>[[order_code]]</strong> a été ramassée par le livreur sur <strong>[[store_name]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_picked_up_email_to_seller' => $this->row(
                'Commande ramassée - [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>La commande <strong>[[order_code]]</strong> a été ramassée par le livreur et est en route vers le client.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_picked_up_email_to_customer' => $this->row(
                'Votre commande [[order_code]] a été ramassée !',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Votre commande <strong>[[order_code]]</strong> a été ramassée par notre livreur et est en route vers vous !</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_on_the_way_email_to_admin' => $this->row(
                'Commande en route - [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La commande <strong>[[order_code]]</strong> est actuellement en cours de livraison sur <strong>[[store_name]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_on_the_way_email_to_seller' => $this->row(
                'Commande en route - [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>La commande <strong>[[order_code]]</strong> est actuellement en cours de livraison vers le client.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_on_the_way_email_to_customer' => $this->row(
                'Votre commande [[order_code]] est en route !',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Bonne nouvelle ! Votre commande <strong>[[order_code]]</strong> est en cours de livraison. Le livreur arrivera bientôt.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_delivered_email_to_admin' => $this->row(
                'Commande livrée - [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La commande <strong>[[order_code]]</strong> a été livrée avec succès sur <strong>[[store_name]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_delivered_email_to_seller' => $this->row(
                'Commande livrée - [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>La commande <strong>[[order_code]]</strong> a été livrée avec succès au client. Merci pour votre collaboration !</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_delivered_email_to_customer' => $this->row(
                'Votre commande [[order_code]] a été livrée !',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Votre commande <strong>[[order_code]]</strong> a été livrée avec succès. Nous espérons que vous êtes satisfait(e) de votre achat.</p>
<p>Si vous avez des questions, n\'hésitez pas à nous contacter.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_cancelled_email_to_admin' => $this->row(
                'Commande annulée - [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La commande <strong>[[order_code]]</strong> a été annulée sur <strong>[[store_name]]</strong>.</p>
<p>Connectez-vous au panneau d\'administration pour plus de détails.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_cancelled_email_to_seller' => $this->row(
                'Commande annulée - [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>La commande <strong>[[order_code]]</strong> dans votre boutique a été annulée.</p>
<p>Connectez-vous à votre espace vendeur pour plus de détails.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_cancelled_email_to_customer' => $this->row(
                'Votre commande [[order_code]] a été annulée',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Votre commande <strong>[[order_code]]</strong> sur <strong>[[store_name]]</strong> a été annulée.</p>
<p>Si vous avez des questions concernant cette annulation, veuillez nous contacter.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_paid_email_to_admin' => $this->row(
                'Paiement reçu pour la commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>Un paiement a été reçu pour la commande <strong>[[order_code]]</strong> sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">N° commande</td><td style="border:1px solid #eee;padding:8px"><strong>[[order_code]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant</td><td style="border:1px solid #eee;padding:8px">[[order_amount]]</td></tr>
</table>
<p style="margin-top:16px">Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_paid_email_to_seller' => $this->row(
                'Paiement reçu pour la commande [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Le paiement pour la commande <strong>[[order_code]]</strong> dans votre boutique a été confirmé.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'order_paid_email_to_customer' => $this->row(
                'Confirmation de paiement - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Nous avons bien reçu votre paiement pour la commande <strong>[[order_code]]</strong> sur <strong>[[store_name]]</strong>.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">N° commande</td><td style="border:1px solid #eee;padding:8px"><strong>[[order_code]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant</td><td style="border:1px solid #eee;padding:8px">[[order_amount]]</td></tr>
</table>
<p style="margin-top:16px">Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            // ── REMBOURSEMENTS ──────────────────────────────────────────────────

            'refund_request_email_to_admin' => $this->row(
                'Nouvelle demande de remboursement - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>Une nouvelle demande de remboursement a été soumise pour la commande <strong>[[order_code]]</strong> sur <strong>[[store_name]]</strong>.</p>
<p>Connectez-vous au panneau d\'administration pour traiter cette demande.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_request_email_to_seller' => $this->row(
                'Nouvelle demande de remboursement - commande [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Une nouvelle demande de remboursement a été soumise pour la commande <strong>[[order_code]]</strong> dans votre boutique.</p>
<p>Connectez-vous à votre espace vendeur pour traiter cette demande.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_request_email_to_customer' => $this->row(
                'Votre demande de remboursement a été reçue - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Nous avons bien reçu votre demande de remboursement pour la commande <strong>[[order_code]]</strong>.</p>
<p>Votre demande est en cours d\'examen. Nous vous informerons de la suite par e-mail.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_accepted_by_admin_email_to_admin' => $this->row(
                'Remboursement accepté - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La demande de remboursement pour la commande <strong>[[order_code]]</strong> a été acceptée.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_accepted_by_seller_email_to_admin' => $this->row(
                'Remboursement accepté par [[shop_name]] - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La boutique <strong>[[shop_name]]</strong> a accepté la demande de remboursement pour la commande <strong>[[order_code]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_accepted_by_admin_email_to_seller' => $this->row(
                'Demande de remboursement acceptée - commande [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>L\'administration a accepté la demande de remboursement pour la commande <strong>[[order_code]]</strong> dans votre boutique.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_accepted_by_seller_email_to_seller' => $this->row(
                'Remboursement accepté - commande [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Vous avez accepté la demande de remboursement pour la commande <strong>[[order_code]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_request_accepted_email_to_customer' => $this->row(
                'Votre remboursement a été accepté - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Nous avons le plaisir de vous informer que votre demande de remboursement pour la commande <strong>[[order_code]]</strong> a été acceptée.</p>
<p>Le remboursement sera traité dans les meilleurs délais.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_denied_by_admin_email_to_admin' => $this->row(
                'Remboursement refusé - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La demande de remboursement pour la commande <strong>[[order_code]]</strong> a été refusée.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_denied_by_seller_email_to_admin' => $this->row(
                'Remboursement refusé par [[shop_name]] - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[admin_name]],</p>
<p>La boutique <strong>[[shop_name]]</strong> a refusé la demande de remboursement pour la commande <strong>[[order_code]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_denied_by_admin_email_to_seller' => $this->row(
                'Demande de remboursement refusée - commande [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>L\'administration a refusé la demande de remboursement pour la commande <strong>[[order_code]]</strong> dans votre boutique.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_denied_by_seller_email_to_seller' => $this->row(
                'Remboursement refusé - commande [[order_code]]',
                $this->tpl('
<p>Bonjour,</p>
<p>Vous avez refusé la demande de remboursement pour la commande <strong>[[order_code]]</strong>.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            'refund_request_denied_email_to_customer' => $this->row(
                'Votre demande de remboursement a été refusée - commande [[order_code]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Nous sommes au regret de vous informer que votre demande de remboursement pour la commande <strong>[[order_code]]</strong> a été refusée.</p>
<p>Si vous souhaitez plus d\'informations, veuillez contacter notre service client.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            // ── APPROBATION BOUTIQUE VENDEUR ────────────────────────────────────

            'seller_shop_approval_email' => $this->row(
                'Félicitations ! Votre boutique [[seller_shop_name]] a été approuvée',
                $this->tpl('
<p>Bonjour,</p>
<p>Félicitations ! Votre boutique <strong>[[seller_shop_name]]</strong> sur <strong>[[store_name]]</strong> a été approuvée. Vous pouvez maintenant commencer à vendre vos produits.</p>
<p>Connectez-vous à votre espace vendeur pour commencer.</p>
<p>Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

            // ── PORTEFEUILLE ────────────────────────────────────────────────────

            'wallet_recharge_email_to_customer' => $this->row(
                'Votre portefeuille a été rechargé sur [[store_name]]',
                $this->tpl('
<p>Bonjour [[customer_name]],</p>
<p>Votre portefeuille sur <strong>[[store_name]]</strong> a été rechargé avec succès.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:480px">
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Montant rechargé</td><td style="border:1px solid #eee;padding:8px"><strong>[[amount]]</strong></td></tr>
  <tr><td style="border:1px solid #eee;padding:8px;color:#666">Date</td><td style="border:1px solid #eee;padding:8px">[[date]]</td></tr>
</table>
<p style="margin-top:16px">Cordialement,<br>L\'équipe [[store_name]]</p>')
            ),

        ];

        foreach ($templates as $identifier => $data) {
            DB::table('email_templates')
                ->where('identifier', $identifier)
                ->update($data);
        }
    }

    public function down()
    {
        // La restauration nécessite une sauvegarde de la base de données.
        // Cette migration ne peut pas être annulée automatiquement.
    }
}
