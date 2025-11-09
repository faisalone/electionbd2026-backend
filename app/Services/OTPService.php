<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OTPService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Generate and send OTP
     */
    public function generateAndSend(string $phoneNumber, string $purpose, ?int $pollId = null): ?Otp
    {
        // Invalidate any previous OTPs for this phone and purpose
        Otp::where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->where('poll_id', $pollId)
            ->where('is_verified', false)
            ->update(['is_verified' => true]); // Mark as used to prevent reuse

        // Generate OTP code (4 digits in Bengali)
        $otpCode = $this->generateOTPCode();

        // Create OTP record
        $otp = Otp::create([
            'phone_number' => $phoneNumber,
            'otp_code' => $otpCode,
            'purpose' => $purpose,
            'poll_id' => $pollId,
            'is_verified' => false,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Send OTP via WhatsApp
        $sent = $this->whatsappService->sendOTP($phoneNumber, $otpCode, $purpose);

        if (!$sent) {
            // If sending failed, delete the OTP
            $otp->delete();
            return null;
        }

        return $otp;
    }

    /**
     * Verify OTP
     */
    public function verify(string $phoneNumber, string $otpCode, string $purpose, ?int $pollId = null): bool
    {
        $otp = Otp::where('phone_number', $phoneNumber)
            ->where('otp_code', $otpCode)
            ->where('purpose', $purpose)
            ->where('poll_id', $pollId)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            return false;
        }

        // Mark OTP as verified
        $otp->update(['is_verified' => true]);

        // Create or update user
        $this->createOrUpdateUser($phoneNumber);

        return true;
    }

    /**
     * Generate 4-digit OTP code in Bengali
     */
    private function generateOTPCode(): string
    {
        $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $this->toBengaliNumber($code);
    }

    /**
     * Convert English numbers to Bengali
     */
    private function toBengaliNumber($number): string
    {
        $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($englishDigits, $bengaliDigits, (string)$number);
    }

    /**
     * Create or update user
     */
    private function createOrUpdateUser(string $phoneNumber): User
    {
        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            $user = User::create([
                'name' => 'User ' . substr($phoneNumber, -4),
                'phone_number' => $phoneNumber,
                'phone_verified_at' => Carbon::now(),
            ]);
        } else if (!$user->phone_verified_at) {
            $user->update(['phone_verified_at' => Carbon::now()]);
        }

        return $user;
    }

    /**
     * Clean up expired OTPs
     */
    public function cleanupExpired(): int
    {
        return Otp::where('expires_at', '<', Carbon::now())
            ->orWhere('is_verified', true)
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->delete();
    }
}
