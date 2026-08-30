<?php

namespace Modules\Tasks\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tasks\Entities\TaskModuleRole;

class TaskRoleSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $roles = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Supervisor'],
        ];

        foreach ($roles as $role) {
            TaskModuleRole::updateOrCreate(
                ['id' => $role['id']], // search by id
                ['name' => $role['name']] // update or create with this name
            );
        }
        // $this->call("OthersTableSeeder");
    }
}
