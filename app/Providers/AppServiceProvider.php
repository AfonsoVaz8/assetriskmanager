<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            if (Schema::hasTable('settings')) {
                $settings = Setting::where('key', 'like', 'mail_%')->pluck('value', 'key')->toArray();

                if (!empty($settings)) {
                    Config::set('mail.mailers.smtp.host', $settings['mail_host'] ?? config('mail.mailers.smtp.host'));
                    Config::set('mail.mailers.smtp.port', $settings['mail_port'] ?? config('mail.mailers.smtp.port'));
                    
                    $encryption = $settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption');
                    Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
                    
                    Config::set('mail.mailers.smtp.username', empty($settings['mail_username']) ? null : $settings['mail_username']);
                    
                    if (!empty($settings['mail_password'])) {
                        Config::set('mail.mailers.smtp.password', Crypt::decryptString($settings['mail_password']));
                    } else {
                        Config::set('mail.mailers.smtp.password', null);
                    }

                    Config::set('mail.from.address', $settings['mail_from_address'] ?? config('mail.from.address'));
                    Config::set('mail.from.name', $settings['mail_from_name'] ?? config('mail.from.name'));
                }
            }
        } catch (\Exception $e) {
        }
    }
}