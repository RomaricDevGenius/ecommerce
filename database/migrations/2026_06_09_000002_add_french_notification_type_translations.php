<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\NotificationType;
use App\Models\NotificationTypeTranslation;

/**
 * Ajoute les traductions françaises des types de notifications (cloche en-tête).
 * Le texte affiché vient de notification_types.default_text via getTranslation('default_text') ;
 * sans ligne FR, le système retombe sur l'anglais. On insère donc la version FR de chaque type.
 * Les variables [[order_code]] / [[shop_name]] / [[amount]] / [[product_name]] sont conservées.
 * Idempotent : updateOrCreate par (notification_type_id, lang='fr'). N'affecte que le français.
 */
class AddFrenchNotificationTypeTranslations extends Migration
{
    public function up()
    {
        // Clé = colonne `type` (identifiant stable, indépendant des id qui peuvent différer en prod)
        $fr = [
            // Commandes
            'order_placed_admin'        => ['Commande passée',        'Commande : [[order_code]] a été passée'],
            'order_placed_seller'       => ['Commande passée',        'Commande : [[order_code]] a été passée'],
            'order_placed_customer'     => ['Commande passée',        'Votre commande : [[order_code]] a été passée'],
            'order_confirmed_admin'     => ['Commande confirmée',     'Commande : [[order_code]] a été confirmée'],
            'order_confirmed_seller'    => ['Commande confirmée',     'Commande : [[order_code]] a été confirmée'],
            'order_confirmed_customer'  => ['Commande confirmée',     'Votre commande : [[order_code]] a été confirmée'],
            'order_picked_up_admin'     => ['Commande récupérée',     'Commande : [[order_code]] a été récupérée'],
            'order_picked_up_seller'    => ['Commande récupérée',     'Commande : [[order_code]] a été récupérée'],
            'order_picked_up_customer'  => ['Commande récupérée',     'Votre commande : [[order_code]] a été récupérée'],
            'order_on_the_way_admin'    => ['Commande en route',      'Commande : [[order_code]] est en route'],
            'order_on_the_way_seller'   => ['Commande en route',      'Commande : [[order_code]] est en route'],
            'order_on_the_way_customer' => ['Commande en route',      'Votre commande : [[order_code]] est en route'],
            'order_delivered_admin'     => ['Commande livrée',        'Commande : [[order_code]] a été livrée'],
            'order_delivered_seller'    => ['Commande livrée',        'Commande : [[order_code]] a été livrée'],
            'order_delivered_customer'  => ['Commande livrée',        'Votre commande : [[order_code]] a été livrée'],
            'order_cancelled_admin'     => ['Commande annulée',       'Commande : [[order_code]] a été annulée'],
            'order_cancelled_seller'    => ['Commande annulée',       'Commande : [[order_code]] a été annulée'],
            'order_cancelled_customer'  => ['Commande annulée',       'Votre commande : [[order_code]] a été annulée'],
            'order_paid_admin'          => ['Paiement réussi',        'Le paiement de la commande : [[order_code]] a réussi'],
            'order_paid_seller'         => ['Paiement réussi',        'Le paiement de la commande : [[order_code]] a réussi'],
            'order_paid_customer'       => ['Paiement réussi',        'Votre paiement pour la commande : [[order_code]] a réussi'],
            // Produits
            'seller_product_upload'     => ['Produit ajouté',         'Produit : [[product_name]] est en attente'],
            'seller_product_approved'   => ['Produit approuvé',       'Produit : [[product_name]] est approuvé'],
            // Paiements vendeur
            'seller_payout_request'     => ['Demande de paiement',    '[[shop_name]] demande un paiement : [[amount]]'],
            'seller_payout'             => ['Paiement effectué',      '[[shop_name]] : le paiement de [[amount]] a été effectué'],
            // Vérification boutique
            'shop_verify_request_submitted' => ['Demande de vérification soumise',  '[[shop_name]] a soumis une demande de vérification.'],
            'shop_verify_request_approved'  => ['Demande de vérification approuvée', 'Votre demande de vérification de boutique a été approuvée.'],
            'shop_verify_request_rejected'  => ['Demande de vérification rejetée',   'Votre demande de vérification de boutique a été rejetée.'],
            // Commande impayée
            'complete_unpaid_order_payment' => ['Commande impayée',   'Votre commande : [[order_code]] n\'est pas encore payée. Merci de finaliser votre paiement.'],
        ];

        foreach ($fr as $type => $values) {
            $notificationType = NotificationType::where('type', $type)->first();
            if (!$notificationType) {
                continue; // type absent sur cette instance → on ignore sans planter
            }

            NotificationTypeTranslation::updateOrCreate(
                ['notification_type_id' => $notificationType->id, 'lang' => 'fr'],
                ['name' => $values[0], 'default_text' => $values[1]]
            );
        }
    }

    public function down()
    {
        $types = [
            'order_placed_admin', 'order_placed_seller', 'order_placed_customer',
            'order_confirmed_admin', 'order_confirmed_seller', 'order_confirmed_customer',
            'order_picked_up_admin', 'order_picked_up_seller', 'order_picked_up_customer',
            'order_on_the_way_admin', 'order_on_the_way_seller', 'order_on_the_way_customer',
            'order_delivered_admin', 'order_delivered_seller', 'order_delivered_customer',
            'order_cancelled_admin', 'order_cancelled_seller', 'order_cancelled_customer',
            'order_paid_admin', 'order_paid_seller', 'order_paid_customer',
            'seller_product_upload', 'seller_product_approved',
            'seller_payout_request', 'seller_payout',
            'shop_verify_request_submitted', 'shop_verify_request_approved', 'shop_verify_request_rejected',
            'complete_unpaid_order_payment',
        ];

        $ids = NotificationType::whereIn('type', $types)->pluck('id');
        NotificationTypeTranslation::whereIn('notification_type_id', $ids)->where('lang', 'fr')->delete();
    }
}
