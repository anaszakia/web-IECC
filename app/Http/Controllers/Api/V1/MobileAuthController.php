<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    /**
     * POST /api/v1/auth/login
     * Login untuk Mobile App (Citizen & Field Officer)
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
            'app_type' => 'nullable|in:citizen,field',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::with(['role', 'agency', 'unitMember.unit'])->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi tidak cocok.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.',
            ], 403);
        }

        // Generate token session sederhana untuk API mobile
        $token = Str::random(64);

        // Ambil data unit armada jika user adalah anggota unit atau petugas lapangan
        $assignedUnit = $user->unitMember?->unit;
        if (!$assignedUnit && $user->agency_id) {
            $assignedUnit = \App\Models\Master\Unit::where('agency_id', $user->agency_id)->first();
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'token'     => $token,
                'user'      => [
                    'ulid'      => $user->ulid,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'phone'     => $user->phone,
                    'user_type' => $user->user_type,
                    'role'      => $user->role?->slug,
                    'agency'    => $user->agency ? [
                        'code' => $user->agency->code,
                        'name' => $user->agency->name,
                        'type' => $user->agency->type,
                    ] : null,
                    'unit'      => $assignedUnit ? [
                        'ulid'       => $assignedUnit->ulid,
                        'code'       => $assignedUnit->code,
                        'type'       => $assignedUnit->type,
                        'status'     => $assignedUnit->status,
                        'lat'        => (float) $assignedUnit->lat,
                        'lng'        => (float) $assignedUnit->lng,
                        'crew_ready' => (bool) $assignedUnit->crew_ready,
                    ] : null,
                ],
            ],
        ]);
    }
}
