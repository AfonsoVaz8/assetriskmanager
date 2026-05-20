<div>
    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="tabs"
            data-tabs-toggle="#tabsContent" role="tabList">
            <li class="mr-2" role="presentation">
                <button
                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                        id="details-tab" data-tabs-target="#details" type="button" role="tab"
                        aria-controls="details" aria-selected="true">{{__("Details")}}
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button
                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                        id="threats-controls-tab" data-tabs-target="#threats_controls" type="button"
                        role="tab"
                        aria-controls="threats_controls"
                        aria-selected="false">{{__("Threats/Controls")}}
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button
                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                        id="vulnerabilities-tab" data-tabs-target="#vulnerabilities" type="button" role="tab"
                        aria-controls="vulnerabilities" aria-selected="false">{{__("Vulnerabilities")}}
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button
                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                        id="risk-summary-tab" data-tabs-target="#risk_summary" type="button" role="tab"
                        aria-controls="risk_summary" aria-selected="false">{{__("Risk Summary")}}
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button
                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                        id="logs-tab" data-tabs-target="#logs" type="button" role="tab"
                        aria-controls="logs" aria-selected="false">{{__("Logs")}}
                </button>
            </li>
        </ul>
    </div>
    <div id="tabsContent">
        <div class="hidden p-4" id="threats_controls" role="tabpanel"
             aria-labelledby="threats-controls-tab">
            @livewire("asset-threats-controls-manage",["asset"=>$asset])
        </div>

        <div class="hidden p-4" id="vulnerabilities" role="tabpanel" aria-labelledby="vulnerabilities-tab">
            @livewire("asset-vulnerabilities-manage", ["asset" => $asset])
        </div>
        <div class="hidden p-4" id="risk_summary" role="tabpanel"
             aria-labelledby="risk-summary-tab">
            @livewire("asset-risk-summary",["asset"=>$asset])
        </div>

        <div class="hidden p-4" id="risk_summary" role="tabpanel"
             aria-labelledby="risk-summary-tab">
            @livewire("asset-risk-summary",["asset"=>$asset])

        </div>
        <div class="hidden p-4" id="logs" role="tabpanel"
             aria-labelledby="logs-tab">
            @livewire("asset-logs",["asset"=>$asset])

        </div>
        <div class="hidden p-4" id="details" role="tabpanel"
             aria-labelledby="details-tab">
            <form method="POST" action="{{route('assets.update',$asset->id)}}">
                @csrf
                @method("PUT")

                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Basic Information")}}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div class="md:col-span-2">
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Name")}}</label>
                            <input type="text" id="name" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" value="{{ old('name', $asset->name) }}" required>
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Description")}}</label>
                            <textarea name="description" id="description" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">{{ old('description', $asset->description) }}</textarea>
                        </div>

                        <div>
                            <label for="type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Asset Type")}}</label>
                            <select name="type" id="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                @foreach($assetTypes as $assetType)
                                    <option {{$asset->type->id == $assetType->id ? "selected" : ""}} value="{{ $assetType->id }}">{{ $assetType->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="sku" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("SKU/Inventory ID")}}</label>
                            <input type="text" id="sku" name="sku" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('sku', $asset->sku) }}" required>
                        </div>
                    </div>

                    <div class="mt-4">
                        @livewire("asset-manager-manage",["asset"=>$asset])
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Specifications & Network")}}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label for="manufacturer" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer")}}</label>
                            <input type="text" id="manufacturer" name="manufacturer" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('manufacturer', $asset->manufacturer) }}" required>
                        </div>

                        <div>
                            <label for="version" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Model/Version")}}</label>
                            <input type="text" id="version" name="version" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('version', $asset->version) }}">
                        </div>

                        <div>
                            <label for="cpe" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("CPE")}}</label>
                            <input type="text" id="cpe" name="cpe" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('cpe', $asset->cpe) }}" placeholder="ex: cpe:2.3:o:microsoft:windows_10:...">
                            @error('cpe') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="ip_address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("IP Address")}}</label>
                            <input type="text" id="ip_address" name="ip_address" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('ip_address', $asset->ip_address) }}">
                        </div>

                        <div>
                            <label for="mac_address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("MAC Address")}}</label>
                            <input type="text" id="mac_address" name="mac_address" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('mac_address', $asset->mac_address) }}">
                        </div>

                        <div>
                            <label for="fqdn" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("FQDN")}}</label>
                            <input type="text" id="fqdn" name="fqdn" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('fqdn', $asset->fqdn) }}">
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label for="location" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Location")}}</label>
                            <input type="text" id="location" name="location" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('location', $asset->location) }}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-8" x-data="{ visible: {{$asset->manufacturer_contract_type != \App\Enums\ManufacturerContractType::NONE ? "true" : "false"}} }">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Contract Details")}}</h3>

                    <div class="mb-6">
                        <label for="manufacturer_contract_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Type")}}</label>
                        <select name="manufacturer_contract_type" id="manufacturer_contract_type" x-on:change="visible = $event.target.value != '{{\App\Enums\ManufacturerContractType::NONE->value}}'" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            @foreach(\App\Enums\ManufacturerContractType::cases() as $role)
                                <option {{$asset->manufacturer_contract_type == $role ? "selected" : ""}} value="{{ $role->value }}">{{ __("enums.".$role->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="visible" id="contract_details" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="manufacturer_contract_provider" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Provider")}}</label>
                            <input type="text" id="manufacturer_contract_provider" name="manufacturer_contract_provider" value="{{ old('manufacturer_contract_provider', $asset->manufacturer_contract_provider) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label for="manufacturer_contract_beginning_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Beginning Date")}}</label>
                            <input type="date" id="manufacturer_contract_beginning_date" name="manufacturer_contract_beginning_date" value="{{ old('manufacturer_contract_beginning_date', $asset->manufacturer_contract_beginning_date ? (is_string($asset->manufacturer_contract_beginning_date) ? $asset->manufacturer_contract_beginning_date : $asset->manufacturer_contract_beginning_date->format('Y-m-d')) : '') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label for="manufacturer_contract_ending_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Ending Date")}}</label>
                            <input type="date" id="manufacturer_contract_ending_date" name="manufacturer_contract_ending_date" value="{{ old('manufacturer_contract_ending_date', $asset->manufacturer_contract_ending_date ? (is_string($asset->manufacturer_contract_ending_date) ? $asset->manufacturer_contract_ending_date : $asset->manufacturer_contract_ending_date->format('Y-m-d')) : '') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Risk & Status")}}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="information_classification_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Information Classification")}}</label>
                            <select name="information_classification_id" id="information_classification_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">{{__("Select an option...")}}</option>
                                @foreach($infoClassifications as $info)
                                    <option value="{{ $info->id }}" {{ old('information_classification_id', $asset->information_classification_id) == $info->id ? 'selected' : '' }}>
                                        {{ __($info->name) }} ({{__("Level")}}: {{ $info->level }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="risk_classification_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Risk Classification")}}</label>
                            <select name="risk_classification_id" id="risk_classification_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">{{__("Select an option...")}}</option>
                                @foreach($riskClassifications as $risk)
                                    <option value="{{ $risk->id }}" {{ old('risk_classification_id', $asset->risk_classification_id) == $risk->id ? 'selected' : '' }}>
                                        {{ __($risk->name) }} ({{__("Score")}}: {{ $risk->score }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">{{__("Availability")}}</th>
                                    <th scope="col" class="px-6 py-3">{{__("Integrity")}}</th>
                                    <th scope="col" class="px-6 py-3">{{__("Confidentiality")}}</th>
                                    <th scope="col" class="px-6 py-3">{{__("Total")}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="px-6 py-4">
                                        <input type="number" id="availability_appreciation" name="availability_appreciation" min="1" max="5" value="{{ old('availability_appreciation', $asset->availability_appreciation) }}" class="border border-gray-300 text-gray-900 text-sm rounded-lg block p-2.5 font-bold" style="background-color: {{$asset->color($asset->availability_appreciation)}}" required>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="number" id="integrity_appreciation" name="integrity_appreciation" min="1" max="5" value="{{ old('integrity_appreciation', $asset->integrity_appreciation) }}" class="border border-gray-300 text-gray-900 text-sm rounded-lg block p-2.5 font-bold" style="background-color: {{$asset->color($asset->integrity_appreciation)}}" required>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="number" id="confidentiality_appreciation" name="confidentiality_appreciation" min="1" max="5" value="{{ old('confidentiality_appreciation', $asset->confidentiality_appreciation) }}" class="border border-gray-300 text-gray-900 text-sm rounded-lg block p-2.5 font-bold" style="background-color: {{$asset->color($asset->confidentiality_appreciation)}}" required>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="number" id="total_appreciation" value="{{ $asset->totalAppreciation() }}" class="border border-gray-300 text-gray-900 text-sm rounded-lg block p-2.5 font-bold" style="background-color: {{$asset->color($asset->totalAppreciation())}}" readonly>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center gap-8 mb-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <div class="flex items-center">
                            <input id="export" name="export" type="checkbox" value="1" class="w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700" {{$asset->export ? "checked" : ""}}>
                            <label for="export" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Export")}}</label>
                        </div>
                        <div class="flex items-center">
                            <input id="active" name="active" type="checkbox" value="1" class="w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700" {{$asset->active ? "checked" : ""}}>
                            <label for="active" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Active")}}</label>
                        </div>
                    </div>

                    @livewire("asset-links-to-manage",["asset"=>$asset])
                </div>

                <div class="flex items-center justify-end border-t border-gray-200 dark:border-gray-700 pt-6">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-6 py-3 text-center shadow-md dark:bg-blue-600 dark:hover:bg-blue-700">
                        {{__("Update Asset")}}
                    </button>
                </div>
            </form>

            @can("delete",$asset)
                @include("common.delete_prompt",["route" => route("assets.destroy",$asset->id),"message" => __("Are you sure you want to delete this asset? This will delete all associated information with it.")])
            @endcan

            @if(!empty($asset->children))
                <div class="py-2">
                    <div class="flex-grow border-t border-gray-400"></div>
                </div>
                <h2 class="text-center text-2xl font-normal leading-normal mt-0 mb-2">{{__("Children Assets")}}</h2>
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-5">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                {{__("ID")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Name")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Type")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("SKU")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("IP")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("MAC")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Manufacturer")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Location")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Action")}}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($asset->children as $child)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{$child->id}}</td>
                                <td class="px-6 py-4">{{$child->name}}</td>
                                <td class="px-6 py-4">{{$child->type->name}}</td>
                                <td class="px-6 py-4">{{$child->sku}}</td>
                                <td class="px-6 py-4">{{$child->ip_address}}</td>
                                <td class="px-6 py-4">{{$child->mac_address}}</td>
                                <td class="px-6 py-4">{{$child->manufacturer}}</td>
                                <td class="px-6 py-4">{{$child->location}}</td>
                                <td class="px-6 py-4">
                                    @can("update",$child)
                                        <a href="{{route("assets.edit",$child->id)}}"
                                           class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                            {{__("Manage")}}
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
