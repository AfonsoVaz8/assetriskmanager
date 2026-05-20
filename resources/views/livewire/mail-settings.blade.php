<div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">{{ __('Mail Settings (SMTP)') }}</h2>

        @if($message)
            <div class="p-4 mb-6 text-sm rounded-lg {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
                {{ $message }}
            </div>
        @endif

        <form wire:submit="saveSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div class="md:col-span-2">
                <label for="mail_from_name" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Sender Name (From Name)') }}</label>
                <input type="text" id="mail_from_name" wire:model="mail_from_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Ex: Risk Management" required>
            </div>

            <div class="md:col-span-2">
                <label for="mail_from_address" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Sender E-mail (From Address)') }}</label>
                <input type="email" id="mail_from_address" wire:model="mail_from_address" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
            </div>

            <div>
                <label for="mail_host" class="block mb-2 text-sm font-medium text-gray-900">{{ __('SMTP Server (Host)') }}</label>
                <input type="text" id="mail_host" wire:model="mail_host" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="smtp.gmail.com" required>
            </div>

            <div>
                <label for="mail_port" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Port') }}</label>
                <input type="number" id="mail_port" wire:model="mail_port" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="465" required>
            </div>

            <div>
                <label for="mail_encryption" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Encryption') }}</label>
                <select id="mail_encryption" wire:model="mail_encryption" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    <option value="none">{{ __('None') }}</option>
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="smtps">SMTPS</option>
                </select>
            </div>

            <div class="md:col-span-2 border-t pt-4 mt-2">
                <div class="flex items-center mb-4">
                    <input id="requires_auth" type="checkbox" wire:model.live="requires_auth" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <label for="requires_auth" class="ml-2 text-sm font-medium text-gray-900">{{ __('Server Requires Authentication') }}</label>
                </div>
            </div>

            @if($requires_auth)
                <div>
                    <label for="mail_username" class="block mb-2 text-sm font-medium text-gray-900">{{ __('E-mail / Username') }}</label>
                    <input type="email" id="mail_username" wire:model="mail_username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>

                <div>
                    <label for="mail_password" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Password (App Password)') }}</label>
                    <input type="password" id="mail_password" wire:model="mail_password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    <p class="text-xs text-gray-500 mt-1">{{ __('Your password will be securely encrypted in the database.') }}</p>
                </div>
            @endif

            <div class="md:col-span-2 flex justify-end mt-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveSettings" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 shadow-md">
                    <span wire:loading.remove wire:target="saveSettings">{{ __('Save Settings') }}</span>
                    <span wire:loading wire:target="saveSettings">{{ __('Saving...') }}</span>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <h3 class="text-lg font-bold mb-4 text-gray-800">{{ __('Test Connection') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ __('Save your settings first. Then, enter an email address below to test if the system can send emails properly.') }}</p>

        <form wire:submit="sendTestEmail" class="flex gap-4">
            <div class="flex-grow">
                <label for="test_email" class="sr-only">{{ __('Email address to receive test') }}</label>
                <input type="email" id="test_email" wire:model="test_email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="{{ __('Email address to receive test') }}" required>
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="sendTestEmail" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5">
                <span wire:loading.remove wire:target="sendTestEmail">{{ __('Send Test Email') }}</span>
                <span wire:loading wire:target="sendTestEmail">{{ __('Sending...') }}</span>
            </button>
        </form>
    </div>
</div>
