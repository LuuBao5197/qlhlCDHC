<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\Room;
use Illuminate\Database\Seeder;
use Modules\Schedule\Models\ScheduleSlot;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['code' => 'P101', 'name' => 'Phong 101', 'capacity' => 40, 'room_type' => 'classroom', 'status' => 'active'],
            ['code' => 'P102', 'name' => 'Phong 102', 'capacity' => 40, 'room_type' => 'classroom', 'status' => 'active'],
            ['code' => 'LAB201', 'name' => 'Phong Lab 201', 'capacity' => 30, 'room_type' => 'lab', 'status' => 'active'],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(['code' => $room['code']], $room);
        }

        $roomIds = Room::query()->pluck('id')->all();
        if ($roomIds === []) {
            return;
        }

        ScheduleSlot::query()->whereNull('room_id')->orderBy('id')->get()->each(function (ScheduleSlot $slot, int $index) use ($roomIds) {
            $slot->update([
                'room_id' => $roomIds[$index % count($roomIds)],
            ]);
        });
    }
}
