<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Risk Classifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-4">{{ __('Manage Risk Classifications') }}</h2>

                <form wire:submit="save" class="mb-8 space-y-4 bg-gray-50 p-4 rounded">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Name (e.g., High, Medium, Low)') }}</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Risk Score') }}</label>
                            <input type="number" wire:model="score" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('score') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            {{ $editingId ? __('Update') : __('Create New') }}
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $set('name', ''); $set('score', '')" class="text-gray-600 hover:underline">
                                {{ __('Cancel') }}
                            </button>
                        @endif
                    </div>
                </form>

                <table class="min-w-full divide-y divide-gray-200 border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Score') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($classifications as $item)
                            <tr>
                                <td class="px-6 py-4">{{ __($item->name) }}</td>
                                <td class="px-6 py-4">{{ $item->score }}</td>
                                <td class="px-6 py-4">
                                    <button wire:click="edit({{ $item->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Edit') }}</button>
                                    <button wire:click="confirmDeletion({{ $item->id }})" class="text-red-600 hover:text-red-900">
                                        {{ __('Delete') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <x-confirmation-modal wire:model.live="confirmingDeletion">
        <x-slot name="title">
            {{ __('Apagar Classificação') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Tens a certeza que queres apagar esta classificação? Depois de eliminada, não poderá ser recuperada.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
                {{ __('Cancelar') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="delete" wire:loading.attr="disabled">
                {{ __('Apagar') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
