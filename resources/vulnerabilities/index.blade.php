<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Vulnerabilities Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <h3 class="text-lg font-medium text-gray-900 mb-4">Importar Vulnerabilidades via CPE</h3>

                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('vulnerabilities.import') }}" method="POST" class="max-w-md">
                    @csrf
                    
                    <div class="mb-4">
                        <x-label for="asset_id" value="{{ __('Select Asset with CPE') }}" />
                        <select id="asset_id" name="asset_id" class="border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">Selecione um ativo...</option>
                            @foreach($assetsWithCpe as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->name }} (CPE: {{ $asset->cpe }})</option>
                            @endforeach
                        </select>
                        <x-input-error for="asset_id" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-label for="cpe" value="{{ __('CPE String (Auto-preenchido ao selecionar o ativo)') }}" />
                        <x-input id="cpe" class="block mt-1 w-full" type="text" name="cpe" required autofocus />
                        <x-input-error for="cpe" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button>
                            {{ __('Import from NVD') }}
                        </x-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('asset_id').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var text = selectedOption.text;
            var match = text.match(/CPE: (.*?)\)/);
            if (match && match[1]) {
                document.getElementById('cpe').value = match[1];
            } else {
                document.getElementById('cpe').value = '';
            }
        });
    </script>
</x-app-layout>