<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\Room;

class RoomSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $rooms = [
            ['code' => 'P-101', 'name' => 'Phòng học 101', 'capacity' => 60, 'room_type' => 'classroom', 'status' => 'active'],
            ['code' => 'P-102', 'name' => 'Phòng học 102', 'capacity' => 60, 'room_type' => 'classroom', 'status' => 'active'],
            ['code' => 'P-201', 'name' => 'Phòng học 201', 'capacity' => 50, 'room_type' => 'classroom', 'status' => 'active'],
            ['code' => 'P-202', 'name' => 'Phòng thực hành 202', 'capacity' => 30, 'room_type' => 'lab', 'status' => 'active'],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(
                ['code' => $room['code']],
                [
                    'name' => $room['name'],
                    'capacity' => $room['capacity'],
                    'room_type' => $room['room_type'],
                    'status' => $room['status'],
                ]
            );
        }
    }
}
