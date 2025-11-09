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
        $this->apiUrl = 'https://graph.facebook.com/v24.0/';
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.access_token');
    }

    /**
     * Send OTP via WhatsApp using authentication template
     * Template: verification_code (APPROVED)
     * Format: *{{1}}* is your verification code.
     * Includes: Copy code button (autofill)
     */
    public function sendOTP(string $phoneNumber, string $otp, string $purpose = 'verification')
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($phoneNumber),
                'type' => 'template',
                'template' => [
                    'name' => 'verification_code',
                    'language' => [
                        'code' => 'en_US'
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $otp
                                ]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => 0,
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $otp
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp OTP sent successfully', [
                    'phone' => $phoneNumber,
                    'purpose' => $purpose,
                    'message_id' => $response->json()['messages'][0]['id'] ?? null
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
                ? "🎉 *অভিনন্দন!*\n\nআপনি \"{$pollQuestion}\" জরিপের বিজয়ী নির্বাচিত হয়েছেন!\n\nআমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।\n\n✨ নির্বাচন বিডি ২০২৬"
                : "📊 *জরিপ সমাপ্ত*\n\n\"{$pollQuestion}\" জরিপটি সমাপ্ত হয়েছে।\n\nআপনার অংশগ্রহণের জন্য ধন্যবাদ!\n\n🗳️ নির্বাচন বিডি ২০২৬";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->formatPhoneNumber($phoneNumber),
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp poll notification sent', [
                    'phone' => $phoneNumber,
                    'is_winner' => $isWinner,
                    'message_id' => $response->json()['messages'][0]['id'] ?? null
                ]);
            }

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
