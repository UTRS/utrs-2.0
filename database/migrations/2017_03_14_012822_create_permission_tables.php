<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreatePermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config = config('laravel-permission.table_names');

        Schema::create($config['roles'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create($config['permissions'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create($config['user_has_permissions'], function (Blueprint $table) use ($config) {
            $table->integer('user_id')->unsigned();
            $table->integer('permission_id')->unsigned();

            $table->foreign('user_id')
                ->references('id')
                ->on($config['users'])
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on($config['permissions'])
                ->onDelete('cascade');

            $table->primary(['user_id', 'permission_id']);
        });

        Schema::create($config['user_has_roles'], function (Blueprint $table) use ($config) {
            $table->integer('role_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->foreign('role_id')
                ->references('id')
                ->on($config['roles'])
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on($config['users'])
                ->onDelete('cascade');

            $table->primary(['role_id', 'user_id']);
        });

        Schema::create($config['role_has_permissions'], function (Blueprint $table) use ($config) {
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->foreign('permission_id')
                ->references('id')
                ->on($config['permissions'])
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on($config['roles'])
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });

        //Create Roles
        $admin = Role::create(['name' => 'Administrator']);
        $staff = Role::create(['name' => 'WMF Staff']);
        $cu = Role::create(['name' => 'Checkuser']);
        $os = Role::create(['name' => 'Oversighter']);
        $dev = Role::create(['name' => 'Developer']);
        $user = Role::create(['name' => 'User']);

        //Create Permissions
        $permission = Permission::create(['name' => 'view_appeal']);
        $permission = Permission::create(['name' => 'edit_appeal']);

        $permission = Permission::create(['name' => 'view_user']);
        $permission = Permission::create(['name' => 'edit_user']);
        $permission = Permission::create(['name' => 'view_self']);
        $permission = Permission::create(['name' => 'edit_self']);


        $permission = Permission::create(['name' => 'view_templates']);
        $permission = Permission::create(['name' => 'edit_templates']);

        $permission = Permission::create(['name' => 'view_bans']);
        $permission = Permission::create(['name' => 'add_bans']);
        $permission = Permission::create(['name' => 'edit_bans']);
        $permission = Permission::create(['name' => 'del_bans']);

        //Sitenotice
        $permission = Permission::create(['name' => 'view_sitenotice']);
        $permission = Permission::create(['name' => 'edit_sitenotice']);

        //Mass Email
        $permission = Permission::create(['name' => 'massemail']);

        //Hooks
        $permission = Permission::create(['name' => 'add_hooks']);
        $permission = Permission::create(['name' => 'del_hooks']);
        $permission = Permission::create(['name' => 'edit_hooks']);

        //Statistics
        $permission = Permission::create(['name' => 'view_stats']);

        //Search
        $permission = Permission::create(['name' => 'search']);

        //CU Data
        $permission = Permission::create(['name' => 'view_cudata']);

        //OS
        $permission = Permission::create(['name' => 'view_deleted']);
        $permission = Permission::create(['name' => 'edit_deleted']);
        $permission = Permission::create(['name' => 'delete']);
        $permission = Permission::create(['name' => 'restore']);


        //Assign Permissions to User
        $user->givePermissionTo('search');
        $user->givePermissionTo('view_stats');
        $user->givePermissionTo('view_appeal');
        $user->givePermissionTo('edit_appeal');
        $user->givePermissionTo('view_sitenotice');
        $user->givePermissionTo('view_self');
        $user->givePermissionTo('edit_self');

        //Assign Permissions to Staff
        $staff->givePermissionTo('view_cudata');
        $staff->givePermissionTo('view_deleted');
        $staff->givePermissionTo('edit_deleted');

        //Assign Permissions to Checkusers
        $cu->givePermissionTo('view_cudata');

        //Assign Permissions to Oversighters
        $os->givePermissionTo('view_deleted');
        $os->givePermissionTo('edit_deleted');
        $os->givePermissionTo('delete');
        $os->givePermissionTo('restore');

        //Assign Permissions to Developer
        $dev->givePermissionTo('view_deleted');
        $dev->givePermissionTo('edit_deleted');
        $dev->givePermissionTo('delete');
        $dev->givePermissionTo('restore');
        $dev->givePermissionTo('view_cudata');
        $dev->givePermissionTo('view_user');
        $dev->givePermissionTo('edit_user');
        $dev->givePermissionTo('view_templates');
        $dev->givePermissionTo('edit_templates');
        $dev->givePermissionTo('view_bans');
        $dev->givePermissionTo('add_bans');
        $dev->givePermissionTo('edit_bans');
        $dev->givePermissionTo('del_bans');
        $dev->givePermissionTo('edit_sitenotice');
        $dev->givePermissionTo('massemail');
        $dev->givePermissionTo('add_hooks');
        $dev->givePermissionTo('del_hooks');
        $dev->givePermissionTo('edit_hooks');

        //Assign Permissions to Admins
        $admin->givePermissionTo('delete');
        $admin->givePermissionTo('restore');
        $admin->givePermissionTo('view_deleted');
        $admin->givePermissionTo('edit_deleted');
        $admin->givePermissionTo('view_user');
        $admin->givePermissionTo('edit_user');
        $admin->givePermissionTo('view_templates');
        $admin->givePermissionTo('edit_templates');
        $admin->givePermissionTo('view_bans');
        $admin->givePermissionTo('add_bans');
        $admin->givePermissionTo('edit_bans');
        $admin->givePermissionTo('del_bans');
        $admin->givePermissionTo('edit_sitenotice');


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $config = config('laravel-permission.table_names');

        Schema::drop($config['role_has_permissions']);
        Schema::drop($config['user_has_roles']);
        Schema::drop($config['user_has_permissions']);
        Schema::drop($config['roles']);
        Schema::drop($config['permissions']);
    }
}
