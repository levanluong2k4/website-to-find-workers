<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Update core profile fields for the authenticated user.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $user->update([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => trim($validated['phone']),
        ]);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Update customer address.
     */
    public function updateAddress(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $user->update([
            'address' => trim($validated['address']),
        ]);

        return response()->json([
            'message' => 'Cập nhật địa chỉ thành công',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Change the current user password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng',
                'errors' => [
                    'current_password' => ['Mật khẩu hiện tại không đúng'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }

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
