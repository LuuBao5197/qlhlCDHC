<?php

namespace App\Application\Account\UpdateProfile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateProfileHandler
{
    public function handle(UpdateProfileRequest $request): User
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        // Chỉ lấy đúng các trường được phép: name, phone, avatar. email/employee_code
        // không nằm trong validated() nên không thể lọt vào đây dù client cố gửi lên.
        $attributes = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ];

        /** @var UploadedFile|null $avatar */
        $avatar = $request->file('avatar');
        if ($avatar !== null) {
            $previousPath = $user->avatar_path;
            $attributes['avatar_path'] = $avatar->store('avatars', 'public');

            if ($previousPath) {
                Storage::disk('public')->delete($previousPath);
            }
        }

        $user->update($attributes);

        return $user->refresh();
    }
}
