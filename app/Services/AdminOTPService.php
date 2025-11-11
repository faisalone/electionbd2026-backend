<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdminOTPService
{
    /**
     * Generate and send OTP for admin authentication
     */
    public function generateAndSend(string $phoneNumber): array
    {
        // Generate 6-digit OTP
        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in database
        Otp::create([
            'phone_number' => $phoneNumber,
            'otp_code' => $otpCode,
            'purpose' => 'admin_login',
            'poll_id' => null,
            'is_verified' => false,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send via WhatsApp
        try {
            app(WhatsAppService::class)->sendOTP($phoneNumber, $otpCode, 'admin_login');
            
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send admin OTP: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify OTP and authenticate admin
     */
    public function verifyAndAuthenticate(string $phoneNumber, string $otpCode): array
    {
        // Find valid OTP
        $otp = Otp::where('phone_number', $phoneNumber)
            ->where('otp_code', $otpCode)
            ->where('purpose', 'admin_login')
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ];
        }

        // Check if admin exists
        $admin = Admin::where('phone_number', $phoneNumber)->first();

        if (!$admin) {
            return [
                'success' => false,
                'message' => 'Admin not found. Contact system administrator.',
            ];
        }

        if (!$admin->isActive()) {
            return [
                'success' => false,
                'message' => 'Admin account is inactive',
            ];
        }

        // Mark OTP as verified
        $otp->update([
            'is_verified' => true,
        ]);

        // Update admin verification timestamp
        $admin->update([
            'phone_verified_at' => Carbon::now(),
        ]);

        // Generate authentication token (using Sanctum)
        $token = $admin->createToken('admin-token')->plainTextToken;

        return [
            'success' => true,
            'message' => 'Authentication successful',
            'admin' => $admin,
            'token' => $token,
        ];
    }

    /**
     * Logout admin by revoking all tokens
     */
    public function logout(Admin $admin): void
    {
        $admin->tokens()->delete();
    }
}
