<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">{{ __('Company Policies') }}</h2>
        <p class="text-gray-600 text-sm">{{ __('Add and manage your organization\'s policy documents.') }}</p>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md p-6 mb-8 border border-gray-200">
        @if (session()->has('message'))
            <div class="mb-4 text-green-600 font-medium">
                {{ __(session('message')) }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="flex flex-col sm:flex-row items-end gap-4">
            <div class="flex-1 w-full">
                <x-label for="description" value="{{ __('Policy Description') }}" />
                <x-input id="description" type="text" class="mt-1 block w-full" wire:model.defer="description" placeholder="{{ __('E.g.: Privacy Policy') }}" />
                <x-input-error for="description" class="mt-2" />
            </div>

            <div class="flex-1 w-full">
                <x-label for="document" value="{{ __('Attachment (Document)') }}" />
                <input type="file" id="document" wire:model="document" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-1.5 text-sm text-gray-500 file:mr-4 file:py-1 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                <x-input-error for="document" class="mt-2" />
            </div>

            <div class="w-full sm:w-auto mt-4 sm:mt-0">
                <x-button type="submit" wire:loading.attr="disabled">
                    {{ __('Add Policy') }}
                </x-button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Description') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Document') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($policies as $policy)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $policy->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <a href="{{ Storage::url($policy->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 underline">
                                {{ $policy->original_filename }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="deletePolicy({{ $policy->id }})" class="text-red-600 hover:text-red-900 ml-4">{{ __('Remove') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            {{ __('No policies added yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
