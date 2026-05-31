<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDimensionsToProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('length', 8, 2)->default(0)->after('weight');
            $table->decimal('breadth', 8, 2)->default(0)->after('length');
            $table->decimal('height', 8, 2)->default(0)->after('breadth');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['length', 'breadth', 'height']);
        });
    }
}
