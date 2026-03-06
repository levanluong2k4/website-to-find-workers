<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Upload user avatar.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Tối đa 5MB
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar in 'public/avatars'
            $path = $request->file('avatar')->store('avatars', 'public');

            // Update user record
            $user->update([
                'avatar' => $path
            ]);

            return response()->json([
                'message' => 'Tải ảnh đại diện thành công',
                'avatar_url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['message' => 'Không có file nào được tải lên.'], 400);
    }
}
