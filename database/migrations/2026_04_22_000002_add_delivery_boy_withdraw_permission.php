<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddDeliveryBoyWithdrawPermission extends Migration
{
    public function up()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name'  => 'view_delivery_boy_withdraw_requests',
            'guard_name' => 'web',
        ], [
            'section' => 'Delivery Boy',
        ]);

        // Give to super-admin role automatically
        $superAdmin = Role::where('name', 'Super Admin')->orWhere('name', 'Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        $permission = Permission::where('name', 'view_delivery_boy_withdraw_requests')->first();
        if ($permission) {
            $permission->delete();
        }
    }
}
