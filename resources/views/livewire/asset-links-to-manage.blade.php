<div class="mb-6">
    <label for="links_to"
           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Links To Asset")}}</label>
           
    <input type="hidden" name="links_to" id="links_to_hidden" value="{{ $selectedAssetId }}">
    
    @if($showSearch)
        <input
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            type="text" wire:model.live="searchTerm"
            placeholder="{{__("Filter(Name/Description/MAC/IP/SKU/Location/Manufacturer/FQDN)")}}">
        <select id="links_to_select"
                wire:key="select-links-to-box"
                class="form-select appearance-none block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding bg-no-repeat border border-solid border-gray-300 rounded transition ease-in-out m-0 mt-2 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                wire:model="selectedAssetId"
                onchange="document.getElementById('links_to_hidden').value = this.value">
            
            <option value="">{{__("None")}}</option>

            {{-- 1. Mantém o ativo atual visível se não fizer parte dos resultados da pesquisa --}}
            @if(!empty($selectedAssetId) && collect($assets)->where('id', $selectedAssetId)->isEmpty())
                <option value="{{ $selectedAssetId }}">
                    {{ $selectedAssetId }}:{{ optional($asset->linksTo)->name ?? 'Ativo Atual' }}
                </option>
            @endif

            {{-- 2. Removemos o wire:key das opções (o Livewire faz a gestão do select nativamente pelos values) --}}
            @foreach($assets as $linkedAsset)
                <option value="{{ $linkedAsset->id }}">
                    {{ $linkedAsset->id }}:{{ $linkedAsset->name }}
                </option>
            @endforeach
            
        </select>
        <div class="mt-2">
            <button wire:click="toggleSearch(false)" type="button"
                    class="text-gray-900 bg-gray-100 border border-gray-300 focus:outline-none hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 dark:hover:border-gray-600 dark:focus:ring-gray-700">{{__("Done")}}</button>
        </div>
    @else
        <div class="flex gap-2">
            @if(!empty($asset->links_to_id))
                <div class="border-double border-4 border-black p-2 flex-grow">
                    @can("update",$asset->linksTo)
                        <a href="{{route("assets.edit",$asset->links_to_id)}}" id="links_to"
                           class="no-underline hover:underline text-blue-600"
                           target="_blank">{{$asset->linksTo->name}}</a>
                    @else
                        <span id="links_to" class="text-gray-700">{{$asset->linksTo->name}}</span>
                    @endcan
                </div>
            @else
                <div class="p-2 flex-grow text-gray-500 italic">{{__("No links")}}</div>
            @endif
            <button wire:click="toggleSearch" type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">{{__("Edit")}}</button>
        </div>
    @endif
</div>