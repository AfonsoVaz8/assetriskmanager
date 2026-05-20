<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create Attack Surface Scope') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('attack-surface-scopes.store') }}">
                        @csrf
                        @include('attack-surface-scopes._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
