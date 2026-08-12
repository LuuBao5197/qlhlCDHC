<?php

namespace Modules\Training\Application\Management\Rooms;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\Room;

class ManageRoomsHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return Room::class;
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'room_type', 'status'];
    }

    protected function filterableColumns(): array
    {
        return ['status', 'room_type'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('rooms', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'room_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'maintenance'])],
        ];
    }
}