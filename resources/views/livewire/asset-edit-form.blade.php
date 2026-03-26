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
                <div class="mb-6">
                    <label for="name"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Name")}}</label>
                    <input type="text" id="name" name="name"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->name}}"
                           required>
                </div>
                <div class="mb-6">
                    <label for="description"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Description")}}</label>
                    <textarea name="description" id="description"
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">{{$asset->description}}</textarea>
                </div>
                <div class="mb-6">
                    <label for="type"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Asset Type")}}</label>
                    <select name="type" id="type"
                            class="form-select appearance-none block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding bg-no-repeat border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                            required>
                        @foreach($assetTypes as $assetType)
                            <option
                                    {{$asset->type->id == $assetType->id ? "selected" : ""}}
                                    value="{{ $assetType->id }}">
                                {{ $assetType->name  }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @livewire("asset-manager-manage",["asset"=>$asset])
                <div class="mb-6">
                    <label for="sku"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("SKU/Inventory ID")}}</label>
                    <input type="text" id="sku" name="sku"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->sku}}"
                           required>
                </div>
                <div class="mb-6">
                    <label for="manufacturer"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer")}}</label>
                    <input type="text" id="manufacturer" name="manufacturer"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->manufacturer}}"
                           required>
                </div>
                <div class="mb-6">
                    <label for="version"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Model/Version")}}</label>
                    <input type="text" id="version" name="version"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->version}}">
                </div>
                <div class="mb-6">
                    <label for="location"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Location")}}</label>
                    <input type="text" id="location" name="location"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->location}}"
                           required>
                </div>
                <div class="mb-6"
                     x-data="{ visible: {{$asset->manufacturer_contract_type != \App\Enums\ManufacturerContractType::NONE ? "true" : "false"}} }">
                    <label for="manufacturer_contract_type"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Type")}}</label>
                    <select name="manufacturer_contract_type" id="manufacturer_contract_type"
                            x-on:change="visible = $event.target.value != '{{\App\Enums\ManufacturerContractType::NONE->value}}'"
                            class="form-select appearance-none block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding bg-no-repeat border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                            required>
                        @foreach(\App\Enums\ManufacturerContractType::cases() as $role)
                            <option
                                    {{$asset->manufacturer_contract_type == $role ? "selected" : ""}}
                                    value="{{ $role->value }}">
                                {{ __("enums.".$role->name)  }}
                            </option>
                        @endforeach
                    </select>
                    <div
                            x-show="visible"
                            id="contract_details">
                        <div class="mb-6">
                            <label for="manufacturer_contract_provider"
                                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Provider")}}</label>
                            <input type="text" id="manufacturer_contract_provider"
                                   name="manufacturer_contract_provider"
                                   value="{{$asset->manufacturer_contract_provider}}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>
                        <div class="mb-6">
                            <label for="manufacturer_contract_beginning_date"
                                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Beginning Date")}}</label>
                            <input type="date" id="manufacturer_contract_beginning_date"
                                   name="manufacturer_contract_beginning_date"
                                   value="{{$asset->manufacturer_contract_beginning_date ? (is_string($asset->manufacturer_contract_beginning_date) ? $asset->manufacturer_contract_beginning_date : $asset->manufacturer_contract_beginning_date->format('Y-m-d')) : ''}}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>
                        <div class="mb-6">
                            <label for="manufacturer_contract_ending_date"
                                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Ending Date")}}</label>
                            <input type="date" id="manufacturer_contract_ending_date"
                                   name="manufacturer_contract_ending_date"
                                   value="{{$asset->manufacturer_contract_ending_date ? (is_string($asset->manufacturer_contract_ending_date) ? $asset->manufacturer_contract_ending_date : $asset->manufacturer_contract_ending_date->format('Y-m-d')) : ''}}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>
                    </div>
                </div>
                <div class="mb-6">
                    <label for="ip_address"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("IP Address")}}</label>
                    <input type="text" id="ip_address" name="ip_address"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->ip_address}}">
                </div>
                <div class="mb-6">
                    <label for="mac_address"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("MAC Address")}}</label>
                    <input type="text" id="mac_address" name="mac_address"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->mac_address}}">
                </div>
                <div class="mb-6">
                    <label for="fqdn"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("FQDN")}}</label>
                    <input type="text" id="fqdn" name="fqdn"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           value="{{$asset->fqdn}}">
                </div>
                <div class="mb-6">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                {{__("Availability Appreciation")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Integrity Appreciation")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Confidentiality Appreciation")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Total Appreciation")}}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4">
                                <input type="number" id="availability_appreciation"
                                       name="availability_appreciation"
                                       min="1" max="5"
                                       value="{{ $asset->availability_appreciation }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       style="background-color: {{$asset->color($asset->availability_appreciation)}}"
                                       required>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" id="integrity_appreciation"
                                       name="integrity_appreciation"
                                       min="1" max="5"
                                       value="{{ $asset->integrity_appreciation }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       style="background-color: {{$asset->color($asset->integrity_appreciation)}}"
                                       required>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" id="confidentiality_appreciation"
                                       name="confidentiality_appreciation"
                                       min="1" max="5"
                                       value="{{ $asset->confidentiality_appreciation }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       style="background-color: {{$asset->color($asset->confidentiality_appreciation)}}"
                                       required>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" id="total_appreciation"
                                       value="{{ $asset->totalAppreciation() }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       style="background-color: {{$asset->color($asset->totalAppreciation())}}"
                                       readonly>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mb-6">
                    <div class="flex items-center">
                        <input id="export" name="export" type="checkbox" value="1"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                               {{$asset->export ? "checked" : ""}}>
                        <label for="export"
                               class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Export")}}</label>
                    </div>
                </div>
                <div class="mb-6">
                    <div class="flex items-center">
                        <input id="active" name="active" type="checkbox" value="1"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                               {{$asset->active ? "checked" : ""}}>
                        <label for="active"
                               class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Active")}}</label>
                    </div>
                </div>
                @livewire("asset-links-to-manage",["asset"=>$asset])

                <div class="flex items-center justify-end">
                    <button type="submit"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabElements = [
        {
            id: 'details',
            triggerEl: document.querySelector('#details-tab'),
            targetEl: document.querySelector('#details')
        },
        {
            id: 'threats_controls',
            triggerEl: document.querySelector('#threats-controls-tab'),
            targetEl: document.querySelector('#threats_controls')
        },
        {
            id: 'risk_summary',
            triggerEl: document.querySelector('#risk-summary-tab'),
            targetEl: document.querySelector('#risk_summary')
        },
        {
            id: 'logs',
            triggerEl: document.querySelector('#logs-tab'),
            targetEl: document.querySelector('#logs')
        }
    ];

    const options = {
        defaultTabId: 'details',
        activeClasses: 'text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-500 border-blue-600 dark:border-blue-500',
        inactiveClasses: 'text-gray-500 hover:text-gray-600 dark:text-gray-400 border-gray-100 hover:border-gray-300 dark:border-gray-700 dark:hover:text-gray-300',
        onShow: () => {
            console.log('tab is shown');
        }
    };

    const tabs = new Tabs(tabElements, options);
    tabs.show('details');
});
</script>