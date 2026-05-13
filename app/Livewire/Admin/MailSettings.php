<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Enums\UserRole;

class MailSettings extends Component
{
    public $mail_host;
    public $mail_port;
    public $mail_username;
    public $mail_password;
    public $mail_encryption;
    public $mail_from_address;
    public $mail_from_name;

    public $requires_auth = true;
    public $test_email;

    public $message = '';
    public $messageType = 'success'; 

    public function mount()
    {
        abort_if(Auth::user()->role !== UserRole::ADMINISTRATOR, 403, 'Acesso Negado.');

        $settings = Setting::where('key', 'like', 'mail_%')->pluck('value', 'key')->toArray();
        
        $this->mail_host = $settings['mail_host'] ?? config('mail.mailers.smtp.host');
        $this->mail_port = $settings['mail_port'] ?? config('mail.mailers.smtp.port');
        $this->mail_username = $settings['mail_username'] ?? '';
        $this->mail_encryption = $settings['mail_encryption'] ?? 'tls';
        $this->mail_from_address = $settings['mail_from_address'] ?? config('mail.from.address');
        $this->mail_from_name = $settings['mail_from_name'] ?? config('mail.from.name');

        if (isset($settings['mail_password']) && !empty($settings['mail_password'])) {
            $this->mail_password = Crypt::decryptString($settings['mail_password']);
        } else {
            $this->mail_password = '';
        }

        $this->requires_auth = !empty($this->mail_username);
    }

    public function saveSettings()
    {
        $this->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_encryption' => 'required|string',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
            'mail_username' => $this->requires_auth ? 'required|email' : 'nullable',
            'mail_password' => $this->requires_auth ? 'required|string' : 'nullable',
        ]);

        $settingsToSave = [
            'mail_host' => $this->mail_host,
            'mail_port' => $this->mail_port,
            'mail_encryption' => $this->mail_encryption,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name' => $this->mail_from_name,
            'mail_username' => $this->requires_auth ? $this->mail_username : null,
            'mail_password' => ($this->requires_auth && !empty($this->mail_password)) ? Crypt::encryptString($this->mail_password) : null,
        ];

        foreach ($settingsToSave as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->messageType = 'success';
        $this->message = __('Mail settings saved successfully!');
    }

    public function sendTestEmail()
    {
        $this->validate([
            'test_email' => 'required|email'
        ]);

        try {
            Mail::raw(__('This is a test email to confirm that your SMTP settings are working perfectly.'), function ($message) {
                $message->to($this->test_email)
                        ->subject(__('SMTP Configuration Test'));
            });

            $this->messageType = 'success';
            $this->message = __('Test email sent successfully! Please check your inbox.');
            $this->test_email = '';

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SMTP Test Failed: " . $e->getMessage());
            $this->messageType = 'error';
            $this->message = __('Failed to send test email. Error: ') . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.mail-settings');
    }
}