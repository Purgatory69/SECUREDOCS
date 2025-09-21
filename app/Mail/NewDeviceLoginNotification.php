<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDeviceLoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public array $deviceInfo;
    public array $locationInfo;
    public string $loginTime;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $deviceInfo, array $locationInfo)
    {
        $this->user = $user;
        $this->deviceInfo = $deviceInfo;
        $this->locationInfo = $locationInfo;
        $this->loginTime = now()->format('F j, Y \a\t g:i A T');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Device Login - SecureDocs',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-device-login',
            with: [
                'user' => $this->user,
                'deviceInfo' => $this->deviceInfo,
                'locationInfo' => $this->locationInfo,
                'loginTime' => $this->loginTime,
                'deviceType' => $this->getDeviceTypeDisplay(),
                'browserInfo' => $this->getBrowserDisplay(),
                'locationDisplay' => $this->getLocationDisplay(),
            ]
        );
    }

    /**
     * Get display-friendly device type
     */
    protected function getDeviceTypeDisplay(): string
    {
        return match($this->deviceInfo['device_type']) {
            'mobile' => '📱 Mobile Device',
            'tablet' => '📱 Tablet',
            'desktop' => '💻 Desktop Computer',
            'bot' => '🤖 Automated Access',
            default => '❓ Unknown Device'
        };
    }

    /**
     * Get browser display info
     */
    protected function getBrowserDisplay(): string
    {
        $browser = $this->deviceInfo['browser'] ?? 'Unknown';
        $version = $this->deviceInfo['browser_version'] ?? '';
        $platform = $this->deviceInfo['platform'] ?? 'Unknown';
        
        return $version ? "{$browser} {$version} on {$platform}" : "{$browser} on {$platform}";
    }

    /**
     * Get location display
     */
    protected function getLocationDisplay(): string
    {
        $city = $this->locationInfo['city'] ?? 'Unknown';
        $country = $this->locationInfo['country'] ?? 'Unknown';
        
        if ($this->locationInfo['is_local'] ?? false) {
            return '🏠 Local Network';
        }
        
        return "📍 {$city}, {$country}";
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
