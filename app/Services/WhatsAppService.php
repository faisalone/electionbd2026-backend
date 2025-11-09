<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $phoneNumberId;
    protected $accessToken;

    public function __construct()
    {
        $this->apiUrl = 'https://graph.facebook.com/v18.0/';
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.access_token');
    }

    /**
     * Send OTP via WhatsApp
     */
    public function sendOTP(string $phoneNumber, string $otp, string $purpose = 'verification')
    {
        try {
            $message = $this->getOTPMessage($otp, $purpose);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($phoneNumber),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp OTP sent successfully', [
                    'phone' => $phoneNumber,
                    'purpose' => $purpose
                ]);
                return true;
            }

            Log::error('WhatsApp OTP sending failed', [
                'phone' => $phoneNumber,
                'response' => $response->json()
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp OTP exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate OTP message based on purpose
     */
    private function getOTPMessage(string $otp, string $purpose): string
    {
        $messages = [
            'poll_create' => "🗳️ নির্বাচন বিডি ২০২৬\n\nআপনার জরিপ তৈরির OTP: {$otp}\n\nএই কোডটি ৫ মিনিটের জন্য বৈধ।\n\n⚠️ এই কোডটি কাউকে শেয়ার করবেন না।",
            'poll_vote' => "🗳️ নির্বাচন বিডি ২০২৬\n\nআপনার ভোটের OTP: {$otp}\n\nএই কোডটি ৫ মিনিটের জন্য বৈধ।\n\n⚠️ এই কোডটি কাউকে শেয়ার করবেন না।",
            'default' => "🗳️ নির্বাচন বিডি ২০২৬\n\nআপনার যাচাইকরণ কোড: {$otp}\n\nএই কোডটি ৫ মিনিটের জন্য বৈধ।"
        ];

        return $messages[$purpose] ?? $messages['default'];
    }

    /**
     * Format phone number for WhatsApp (international format)
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If starts with 0, replace with 880 (Bangladesh country code)
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '880' . substr($phoneNumber, 1);
        }
        
        // If doesn't start with country code, add it
        if (substr($phoneNumber, 0, 3) !== '880') {
            $phoneNumber = '880' . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    /**
     * Send poll result notification
     */
    public function sendPollResultNotification(string $phoneNumber, string $pollQuestion, bool $isWinner = false)
    {
        try {
            $message = $isWinner 
                ? "🎉 অভিনন্দন!\n\nআপনি \"{$pollQuestion}\" জরিপের বিজয়ী নির্বাচিত হয়েছেন!\n\nআমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।\n\n✨ নির্বাচন বিডি ২০২৬"
                : "📊 জরিপ সমাপ্ত\n\n\"{$pollQuestion}\" জরিপটি সমাপ্ত হয়েছে।\n\nআপনার অংশগ্রহণের জন্য ধন্যবাদ!\n\n🗳️ নির্বাচন বিডি ২০২৬";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($phoneNumber),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp notification exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
