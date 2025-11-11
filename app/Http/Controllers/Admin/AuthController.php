<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminOTPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $adminOTPService;

    public function __construct(AdminOTPService $adminOTPService)
    {
        $this->adminOTPService = $adminOTPService;
    }

    /**
     * Send OTP for admin login
     */
    public function sendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^\+?[0-9]{10,15}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->adminOTPService->generateAndSend($request->phone_number);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Verify OTP and authenticate admin
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->adminOTPService->verifyAndAuthenticate(
            $request->phone_number,
            $request->otp_code
        );

        return response()->json($result, $result['success'] ? 200 : 401);
    }

    /**
     * Get authenticated admin details
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'admin' => $request->user('sanctum'),
        ]);
    }

    /**
     * Logout admin
     */
    public function logout(Request $request)
    {
        $this->adminOTPService->logout($request->user('sanctum'));

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
