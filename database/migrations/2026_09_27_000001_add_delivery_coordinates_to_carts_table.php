<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le panier conservait le coût de livraison calculé, mais pas la position GPS
 * qui l'avait produit. Un client qui quittait le tunnel d'achat retrouvait donc
 * un montant sans pouvoir voir à quel lieu il correspondait, et devait
 * re-pointer sa position alors qu'un prix était déjà affiché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'delivery_lat')) {
                $table->decimal('delivery_lat', 10, 7)->nullable()->after('shipping_cost');
            }
            if (!Schema::hasColumn('carts', 'delivery_lng')) {
                $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'delivery_lat')) {
                $table->dropColumn('delivery_lat');
            }
            if (Schema::hasColumn('carts', 'delivery_lng')) {
                $table->dropColumn('delivery_lng');
            }
        });
    }
};
