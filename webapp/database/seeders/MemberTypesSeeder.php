<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MemberType;
use Illuminate\Database\Seeder;

class MemberTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'individual', 'name' => 'สมาชิกบุคคล', 'description' => 'Individual subscriber'],
            ['code' => 'organization', 'name' => 'สมาชิกองค์กร', 'description' => 'Organization subscriber'],
        ];

        foreach ($types as $type) {
            MemberType::updateOrCreate(['code' => $type['code']], [
                'name' => $type['name'],
                'description' => $type['description'],
                'is_active' => true,
            ]);
        }
    }
}
