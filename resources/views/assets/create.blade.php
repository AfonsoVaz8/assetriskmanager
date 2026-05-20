<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{__("Create Asset")}}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{route('assets.store')}}">
                        @csrf
                        
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Basic Information")}}</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div class="md:col-span-2">
                                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Name")}}</label>
                                    <input type="text" id="name" name="name"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('name')}}" required>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Description")}}</label>
                                    <textarea name="description" id="description" rows="3"
                                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">{{old('description')}}</textarea>
                                </div>
                                
                                <div>
                                    <label for="type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Asset Type")}}</label>
                                    <select name="type" id="type"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required>
                                        <option value="" disabled {{ empty(old('type')) ? 'selected' : '' }}>{{__("Select Type")}}</option>
                                        @foreach($assetTypes as $assetType)
                                            <option {{old('type') == $assetType->id ? "selected" : ""}} value="{{ $assetType->id }}">
                                                {{ $assetType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="sku" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("SKU/Inventory ID")}}</label>
                                    <input type="text" id="sku" name="sku"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('sku')}}" required>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manager")}}</label>
                                    <livewire:user-search></livewire:user-search>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Specifications & Network")}}</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label for="manufacturer" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer")}}</label>
                                    <input type="text" id="manufacturer" name="manufacturer"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('manufacturer')}}" required>
                                </div>
                                
                                <div>
                                    <label for="version" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Model/Version")}}</label>
                                    <input type="text" id="version" name="version"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('version')}}">
                                </div>

                                <div>
                                    <label for="cpe" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("CPE")}}</label>
                                    <input type="text" id="cpe" name="cpe"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('cpe')}}" placeholder="ex: cpe:2.3:o:microsoft:windows_10:...">
                                </div>

                                <div>
                                    <label for="ip_address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("IP Address")}}</label>
                                    <input type="text" id="ip_address" name="ip_address"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('ip_address')}}">
                                </div>

                                <div>
                                    <label for="mac_address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("MAC Address")}}</label>
                                    <input type="text" id="mac_address" name="mac_address"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('mac_address')}}">
                                </div>

                                <div>
                                    <label for="fqdn" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("FQDN")}}</label>
                                    <input type="text" id="fqdn" name="fqdn"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('fqdn')}}">
                                </div>

                                <div class="md:col-span-2 lg:col-span-3">
                                    <label for="location" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Location")}}</label>
                                    <input type="text" id="location" name="location"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           value="{{old('location')}}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8" x-data="{ visible: {{!empty(old('manufacturer_contract_type')) && old('manufacturer_contract_type') != \App\Enums\ManufacturerContractType::NONE->value ? 'true' : 'false'}} }">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Contract Details")}}</h3>
                            
                            <div class="mb-6">
                                <label for="manufacturer_contract_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Type")}}</label>
                                <select name="manufacturer_contract_type" id="manufacturer_contract_type"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        x-on:change="visible = $event.target.value != '{{\App\Enums\ManufacturerContractType::NONE->value}}'"
                                        required>
                                    @foreach(\App\Enums\ManufacturerContractType::cases() as $role)
                                        <option {{old('manufacturer_contract_type') == $role->value ? "selected" : ""}}
                                                value="{{ $role->value }}" {{ $role == \App\Enums\ManufacturerContractType::NONE && empty(old("manufacturer_contract_type")) ? "selected" : "" }}>
                                            {{ __("enums.".$role->name)  }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="visible" id="contract_details">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="manufacturer_contract_provider" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Provider")}}</label>
                                        <input type="text" id="manufacturer_contract_provider" name="manufacturer_contract_provider"
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                               value="{{old('manufacturer_contract_provider')}}">
                                    </div>
                                    
                                    <div>
                                        <label for="contract_date_range_picker" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Date")}}</label>
                                        <div date-rangepicker datepicker-format="yyyy-mm-dd" class="flex items-center" id="contract_date_range_picker">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <input name="manufacturer_contract_beginning_date" id="manufacturer_contract_beginning_date" type="text"
                                                       class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                       value="{{old('manufacturer_contract_beginning_date')}}"
                                                       placeholder="{{__('Contract Starting Date')}}">
                                            </div>
                                            <span class="mx-4 text-gray-500">{{__("to")}}</span>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <input name="manufacturer_contract_ending_date" id="manufacturer_contract_ending_date" type="text"
                                                       class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                       value="{{old('manufacturer_contract_ending_date')}}"
                                                       placeholder="{{__('Contract Ending Date')}}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">{{__("Risk & Status")}}</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="information_classification_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        {{__("Information Classification")}}
                                    </label>
                                    <select name="information_classification_id" id="information_classification_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">{{__("Select an option...")}}</option>
                                        @foreach($infoClassifications as $info)
                                            <option value="{{ $info->id }}" {{ old('information_classification_id') == $info->id ? 'selected' : '' }}>
                                                {{ __($info->name) }} ({{__("Level")}}: {{ $info->level }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            
                                <div>
                                    <label for="risk_classification_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        {{__("Risk Classification")}}
                                    </label>
                                    <select name="risk_classification_id" id="risk_classification_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">{{__("Select an option...")}}</option>
                                        @foreach($riskClassifications as $risk)
                                            <option value="{{ $risk->id }}" {{ old('risk_classification_id') == $risk->id ? 'selected' : '' }}>
                                                {{ __($risk->name) }} ({{__("Score")}}: {{ $risk->score }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center gap-8 mb-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <input type="checkbox" name="export" id="export" value="1"
                                           class="w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700" 
                                           {{!empty(old('export')) ? "checked" : ""}}>
                                    <label for="export" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Export to CNCS?")}}</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <livewire:asset-search></livewire:asset-search>
                            </div>
                        </div>

                        <div class="flex items-center justify-end border-t border-gray-200 dark:border-gray-700 pt-6">
                            <button type="submit"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-8 py-3 text-center shadow-md dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                {{__("Create Asset")}}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>