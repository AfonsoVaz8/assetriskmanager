<div>
    <!-- Formulário de Importação -->
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6 border border-gray-200 dark:border-gray-700">
        @if($message)
            <div class="mb-4 p-3 rounded text-sm {{ $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400' }}">
                {{ $message }}
            </div>
        @endif

        <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-grow w-full md:w-1/2">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Ativo / CPE</label>
                <input type="text" disabled class="bg-gray-200 border border-gray-300 text-gray-500 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400" value="{{ $asset->name }} (CPE: {{ $asset->cpe ?? 'Não definido' }})">
            </div>

            <div class="w-full md:w-1/3">
                <label for="source" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Data Source")}}</label>
                <select wire:model="source" id="source" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="nvd">NVD (EUA)</option>
                    <option value="euvd">EUVD (Europa)</option>
                </select>
            </div>

            <button wire:click="importVulnerabilities" wire:loading.attr="disabled" {{ empty($asset->cpe) ? 'disabled' : '' }} class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                <span wire:loading.remove wire:target="importVulnerabilities">{{__("Importar")}}</span>
                <span wire:loading wire:target="importVulnerabilities">A importar...</span>
            </button>
        </div>
        @if(empty($asset->cpe))
            <p class="text-xs text-red-500 mt-2">Defina o CPE na aba "Details" e grave o ativo antes de importar.</p>
        @endif
    </div>

    <!-- Tabela das Novas Vulnerabilidades -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 w-1/6">CVE ID</th>
                    <th scope="col" class="px-6 py-3 w-1/2">Descrição</th>
                    <th scope="col" class="px-6 py-3 text-center">Fonte</th>
                    <th scope="col" class="px-6 py-3 text-center">Score Base</th>
                    <th scope="col" class="px-6 py-3 text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assetVulnerabilities as $vulnerability)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white align-top">
                            {{ $vulnerability->cve_id }}
                        </td>
                        <td class="px-6 py-4 align-top">
                            <!-- Scroll box para a descrição -->
                            <div class="max-h-32 overflow-y-auto pr-2 text-justify">
                                {{ $vulnerability->description }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center align-top">
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800">{{ $vulnerability->source }}</span>
                        </td>
                        <td class="px-6 py-4 text-center align-top">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="background-color: {{ \App\Models\Asset::color($vulnerability->pivot->probability) }}; color: black;">
                                {{ $vulnerability->pivot->probability }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center align-top">
                            <button wire:click="confirmVulnerabilityRemoval({{ $vulnerability->id }})" 
                                    class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-800">
                                Remover
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Nenhuma vulnerabilidade associada a este ativo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Modal de Confirmação de Remoção -->
    @if($confirmingVulnerabilityRemoval)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900 bg-opacity-50 transition-opacity">
            <div class="relative w-full max-w-md p-4">
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-800 border dark:border-gray-700">
                    <div class="p-6 text-center">
                        <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
                            Tem a certeza que deseja remover este CVE do ativo?
                        </h3>
                        <div class="flex justify-center gap-3">
                            <button wire:click="removeVulnerability" wire:loading.attr="disabled" type="button" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center disabled:opacity-50">
                                <span wire:loading.remove wire:target="removeVulnerability">Sim, remover</span>
                                <span wire:loading wire:target="removeVulnerability">A remover...</span>
                            </button>
                            <button wire:click="$set('confirmingVulnerabilityRemoval', false)" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>