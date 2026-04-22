<x-action-section>
    <x-slot name="title">
        {{ __('Gestor do Ativo') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Selecione o utilizador responsável pela gestão deste ativo. A alteração será guardada quando clicar em "Update Asset".') }}
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-6 gap-6">
            
            <input type="hidden" name="manager" value="{{ $manager_id }}">

            <div class="col-span-6 sm:col-span-4">
                <x-label for="search" value="{{ __('Pesquisar Gestor') }}" />
                <x-input id="search" type="text" class="mt-1 block w-full" 
                         wire:model.live.debounce.300ms="search" 
                         placeholder="{{ __('Escreva o nome ou email para filtrar...') }}" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label for="manager_select" value="{{ __('Selecionar Gestor da Lista') }}" />
                
                <select id="manager_select" wire:model.live="manager_id" 
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full mt-1">
                    <option value="">{{ __('Selecione um utilizador...') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                
                @error('manager_id') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-6">
                <div class="text-sm text-gray-600">
                    <strong>{{ __('Gestor Atual Guardado:') }}</strong> 
                    {{ $asset->manager->name ?? __('Nenhum') }}
                </div>
            </div>
        </div>
    </x-slot>
</x-action-section>