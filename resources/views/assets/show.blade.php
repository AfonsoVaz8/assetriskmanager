<x-app-layout>
    @php
        $externalExposure = $externalExposure ?? [
            'linked_hosts' => collect(),
            'linked_hosts_count' => 0,
            'recent_runs' => collect(),
            'latest_run' => null,
            'providers' => [],
            'technical_profile' => [],
            'open_ports' => [],
            'vulnerability_count' => 0,
        ];
        $technicalProfile = $externalExposure['technical_profile'] ?? [];
        $linkedHosts = $externalExposure['linked_hosts'] ?? collect();
        $recentRuns = $externalExposure['recent_runs'] ?? collect();
        $latestRun = $externalExposure['latest_run'] ?? null;
        $providers = $externalExposure['providers'] ?? [];
        $services = $technicalProfile['services'] ?? [];
        $technologies = $technicalProfile['technologies'] ?? [];
        $vulnerabilities = $technicalProfile['vulnerabilities'] ?? [];
        $certificates = $technicalProfile['certificates'] ?? [];
    @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{__("View Asset")}}</h2>
    </x-slot>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4 border-b border-gray-200">
                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="tabs"
                            data-tabs-toggle="#tabsContent" role="tabList">
                            <li class="mr-2" role="presentation">
                                <button
                                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="dashboard-tab" data-tabs-target="#details" type="button" role="tab"
                                        aria-controls="details" aria-selected="true">{{__("Details")}}
                                </button>
                            </li>
                            <li class="mr-2" role="presentation">
                                <button
                                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="settings-tab" data-tabs-target="#threats_controls" type="button" role="tab"
                                        aria-controls="threats_controls"
                                        aria-selected="false">{{__("Threats/Controls")}}
                                </button>
                            </li>
                            <li class="mr-2" role="presentation">
                                <button
                                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="settings-tab" data-tabs-target="#risk_summary" type="button" role="tab"
                                        aria-controls="risk_summary" aria-selected="false">{{__("Risk Summary")}}
                                </button>
                            </li>
                            <li class="mr-2" role="presentation">
                                <button
                                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="external-enrichment-tab" data-tabs-target="#external_enrichment" type="button" role="tab"
                                        aria-controls="external_enrichment" aria-selected="false">{{__("External Enrichment")}}
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
                        <div class="hidden p-4" id="details" role="tabpanel"
                             aria-labelledby="details-tab">
                            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-blue-900">{{ __('External Enrichment Summary') }}</h3>
                                        <p class="mt-1 text-sm text-blue-800">
                                            {{ __('This asset is linked to :count discovered host(s). External enrichment has identified the most relevant exposure data below.', ['count' => $externalExposure['linked_hosts_count']]) }}
                                        </p>
                                    </div>
                                    <div class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-blue-900 shadow-sm">
                                        {{ __('Open the "External Enrichment" tab for the full scanner and enrichment breakdown.') }}
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-4">
                                    <div class="rounded-lg bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Linked Hosts') }}</div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $externalExposure['linked_hosts_count'] }}</div>
                                    </div>
                                    <div class="rounded-lg bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Latest Enrichment') }}</div>
                                        <div class="mt-2 text-sm font-semibold text-gray-900">
                                            {{ $latestRun ? \Illuminate\Support\Str::of((string) $latestRun->status)->replace('_', ' ')->title() : __('No enrichment recorded') }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $latestRun?->synced_at?->diffForHumans() ?? __('No successful run yet') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Observed Ports') }}</div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ count($externalExposure['open_ports'] ?? []) }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ !empty($externalExposure['open_ports']) ? collect($externalExposure['open_ports'])->take(6)->implode(', ') : __('No normalized ports yet') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Vulnerabilities') }}</div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ count($vulnerabilities) }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ !empty($providers) ? collect($providers)->map(fn ($provider) => $assetExternalExposureService->providerLabel((string) $provider))->implode(', ') : __('No providers observed yet') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="name"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Name")}}</label>
                                <input type="text" id="name" name="name"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->name}}"
                                       required disabled>
                            </div>
                            <div class="mb-6">
                                <label for="description"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Description")}}</label>
                                <textarea name="description" id="description"
                                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                          disabled>{{$asset->description}}</textarea>
                            </div>
                            <div class="mb-6">
                                <label for="type"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Asset Type")}}</label>
                                <select name="type" id="type"
                                        class="form-select appearance-none block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding bg-no-repeat border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                                        required disabled>
                                    <option value="{{$asset->type->id}}">{{$asset->type->name}}</option>
                                </select>
                            </div>
                            @if(in_array(Auth::user()->role,[\App\Enums\UserRole::SECURITY_OFFICER,\App\Enums\UserRole::DATA_PROTECTION_OFFICER]))
                                <div class="mb-6">
                                    <label for="manager"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manager")}}</label>
                                    <div class="border-double border-4 border-black">
                                        @can("show",$asset->manager)
                                            <a href="{{route("users.show",$asset->manager->id)}}" id="manager"
                                               target="_blank"
                                               class="no-underline hover:underline">{{$asset->manager->name . ":" . $asset->manager->email}}</a>
                                        @else
                                            <a id="manager"
                                               class="no-underline hover:underline">{{$asset->manager->name . ":" . $asset->manager->email}}</a>
                                        @endcan
                                    </div>
                                </div>
                            @endif
                            <div class="mb-6">
                                <label for="sku"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("SKU/Inventory ID")}}</label>
                                <input type="text" id="sku" name="sku"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->sku}}"
                                       required disabled>
                            </div>
                            <div class="mb-6">
                                <label for="manufacturer"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer")}}</label>
                                <input type="text" id="manufacturer" name="manufacturer"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->manufacturer}}"
                                       required disabled>
                            </div>
                            <div class="mb-6">
                                <label for="version"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Model/Version")}}</label>
                                <input type="text" id="version" name="version"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->version}}"
                                       required disabled>
                            </div>
                            <div class="mb-6">
                                <label for="location"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Location")}}</label>
                                <input type="text" id="location" name="location"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->location}}"
                                       required disabled>
                            </div>
                            <div class="mb-6"
                                 x-data="{ visible: {{$asset->manufacturer_contract_type != \App\Enums\ManufacturerContractType::NONE ? "true" : "false"}} }">
                                <label for="manufacturer_contract_type"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Type")}}</label>
                                <select name="manufacturer_contract_type" id="manufacturer_contract_type"
                                        x-on:change="visible = $event.target.value != '{{\App\Enums\ManufacturerContractType::NONE->value}}'"
                                        class="form-select appearance-none block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding bg-no-repeat border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                                        required disabled>
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
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                               disabled>
                                    </div>
                                    <div class="mb-6">
                                        <label for="contract_date_range_picker"
                                               class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Manufacturer Contract Date")}}</label>
                                        <div date-rangepicker datepicker-format="yyyy-mm-dd"
                                             class="flex items-center"
                                             id="contract_date_range_picker">
                                            <div class="relative">
                                                <div
                                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                         fill="currentColor"
                                                         viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd"
                                                              d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                              clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <input name="manufacturer_contract_beginning_date"
                                                       id="manufacturer_contract_beginning_date" type="text"
                                                       class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                       value="{{$asset->manufacturer_contract_beginning_date}}"
                                                       placeholder="{{__('Contract Starting Date')}}" disabled>
                                            </div>
                                            <span class="mx-4 text-gray-500">{{__("to")}}</span>
                                            <div class="relative">
                                                <div
                                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                         fill="currentColor"
                                                         viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd"
                                                              d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                              clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <input name="manufacturer_contract_ending_date"
                                                       id="manufacturer_contract_ending_date" type="text"
                                                       class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                       value="{{$asset->manufacturer_contract_ending_date}}"
                                                       placeholder="{{__('Contract Ending Date')}}" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="mac_address"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("MAC Address")}}</label>
                                <input type="text" id="mac_address" name="mac_address"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->mac_address}}"
                                       required disabled>
                            </div>
                            <div class="mb-6">
                                <label for="fqdn"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("FQDN")}}</label>
                                <input type="text" id="fqdn" name="fqdn"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->fqdn}}"
                                       required disabled>
                            </div>
                            <div class="mb-6">
                                <label for="ip_address"
                                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("IP Address")}}</label>
                                <input type="text" id="ip_address" name="ip_address"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                       value="{{$asset->ip_address}}"
                                       required disabled>
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
                                                   required disabled>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="number" id="integrity_appreciation"
                                                   name="integrity_appreciation"
                                                   min="1" max="5"
                                                   value="{{$asset->integrity_appreciation }}"
                                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                   style="background-color: {{$asset->color($asset->integrity_appreciation)}}"
                                                   required disabled>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="number" id="confidentiality_appreciation"
                                                   name="confidentiality_appreciation"
                                                   min="1" max="5"
                                                   value="{{$asset->confidentiality_appreciation }}"
                                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                   style="background-color: {{$asset->color($asset->confidentiality_appreciation)}}"
                                                   required disabled>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="number" id="total_appreciation"
                                                   value="{{$asset->totalAppreciation() }}"
                                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                   style="background-color: {{$asset->color($asset->totalAppreciation())}}"
                                                   disabled>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mb-6">
                                <label for="export"
                                       class="form-check-label inline-block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Export to CNCS?")}}</label>
                                <input
                                        class="form-check-input appearance-none h-4 w-4 border border-gray-300 rounded-sm bg-white checked:bg-blue-600 checked:border-blue-600 focus:outline-none transition duration-200 mt-1 align-top bg-no-repeat bg-center bg-contain float-left mr-2 cursor-pointer"
                                        type="checkbox" name="export"
                                        id="export" {{$asset->export ? "checked" : ""}} disabled>
                            </div>
                            <div class="mb-6">
                                <label for="active"
                                       class="form-check-label inline-block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Active?")}}</label>
                                <input
                                        class="form-check-input appearance-none h-4 w-4 border border-gray-300 rounded-sm bg-white checked:bg-blue-600 checked:border-blue-600 focus:outline-none transition duration-200 mt-1 align-top bg-no-repeat bg-center bg-contain float-left mr-2 cursor-pointer"
                                        type="checkbox" name="active"
                                        id="active" {{$asset->active ? "checked" : ""}} disabled>
                            </div>
                            @if(!empty($asset->links_to_id))
                                <div class="mb-6">
                                    <label for="links_to"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{__("Links To Asset")}}</label>
                                    <div class="border-double border-4 border-black">
                                        @can("update",$asset->linksTo)
                                            <a href="{{route("assets.edit",$asset->links_to_id)}}" id="links_to"
                                               class="no-underline hover:underline"
                                               target="_blank">{{$asset->linksTo->name}}</a>
                                        @else
                                            <a id="links_to"
                                               class="no-underline hover:underline">{{$asset->linksTo->name}}</a>
                                        @endcan

                                    </div>
                                </div>
                            @endif
                            @if(!empty($children))
                                <div class="flex-grow border-t border-gray-400"></div>
                                <h2 class="text-center text-2xl font-normal leading-normal mt-0 mb-2">{{__("Children")}}</h2>
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
                                        @foreach($children as $child)
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
                                                    <a href="{{route("assets.show",$child->id)}}"
                                                       class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                                        {{__("View")}}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="hidden p-4 bg-gray-50 rounded-lg dark:bg-gray-800" id="threats_controls"
                             role="tabpanel"
                             aria-labelledby="threats-controls-tab">
                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-5">
                                @foreach($asset->threats as $threat)
                                    <table
                                            class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-separate">
                                        <thead
                                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("ID")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Name")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Description")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Probability")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Availability Impact")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Integrity Impact")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Confidentiality Impact")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Absolute Risk")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Total Risk")}}
                                            </th>
                                            <th scope="col" class="px-3 py-3">
                                                {{__("Action")}}
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 text-gray-900">
                                            <td class="px-3 py-4">{{$threat->id}}</td>
                                            <td class="px-3 py-4">{{$threat->threat->name}}</td>
                                            <td class="px-3 py-4">{{$threat->threat->description}}</td>
                                            <td style="background-color: {{$threat->color($threat->probability)}}"
                                                class="px-3 py-4">{{$threat->probability}}</td>
                                            <td style="background-color: {{$threat->color($threat->availability_impact)}}"
                                                class="px-3 py-4">{{$threat->availability_impact}}</td>
                                            <td style="background-color: {{$threat->color($threat->integrity_impact)}}"
                                                class="px-3 py-4">{{$threat->integrity_impact}}</td>
                                            <td style="background-color: {{$threat->color($threat->confidentiality_impact)}}"
                                                class="px-3 py-4">{{$threat->confidentiality_impact}}</td>
                                            <td style="background-color: {{$threat->absoluteRiskColor($threat->absoluteRisk())}}"
                                                class="px-3 py-4">{{$threat->absoluteRisk()}}</td>
                                            <td style="background-color: {{$threat->totalRiskColor($threat->totalRisk($asset->totalAppreciation()))}}"
                                                class="px-3 py-4">
                                                {{$threat->totalRisk($asset->totalAppreciation())}}</td>
                                            <td class="px-3 py-4">
                                                <a href="{{route("threats.show",$threat->threat->id)}}"
                                                   class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                                                   target="_blank">
                                                    {{__("View")}}</a>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    @if($threat->controls()->exists())
                                        <h2 class="text-center text-xl font-normal leading-normal mt-0 mb-2">
                                            {{__("Controls")}}</h2>
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
                                                    {{__("Description")}}
                                                </th>
                                                <th scope="col" class="px-6 py-3">
                                                    {{__("Control Type")}}
                                                </th>
                                                <th scope="col" class="px-6 py-3">
                                                    {{__("Validated?")}}
                                                </th>
                                                <th scope="col" class="px-6 py-3">
                                                    {{__("Action")}}
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($threat->controls as $control)
                                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 text-gray-900">
                                                    <td class="px-6 py-4">{{$control->id}}</td>
                                                    <td class="px-6 py-4">{{$control->control->name}}</td>
                                                    <td class="px-6 py-4">{{$control->control->description}}</td>
                                                    <td class="px-6 py-4">{{  __("enums.".$control->control_type->name) }}</td>

                                                    <td class="px-6 py-4">
                                                        <input
                                                                class="form-check-input appearance-none h-4 w-4 border border-gray-300 rounded-sm bg-white checked:bg-blue-600 checked:border-blue-600 focus:outline-none transition duration-200 mt-1 align-top bg-no-repeat bg-center bg-contain float-left mr-2 cursor-pointer"
                                                                type="checkbox" disabled
                                                                {{$control->validated ? "checked" : ""}}>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <a href="{{route("controls.show",$control->control->id)}}"
                                                           class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                                                           target="_blank">
                                                            {{__("View")}}</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>

                                        @if(!$loop->last)
                                            <div class="py-8">
                                                <div class="flex-grow border-t border-dashed border-gray-400"></div>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>

                        </div>
                        <div class="hidden p-4" id="risk_summary" role="tabpanel"
                             aria-labelledby="risk_summary-tab">
                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-5">
                                <table
                                        class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-separate">
                                    <thead
                                            class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-3 py-3">
                                            {{__("ID")}}
                                        </th>
                                        <th scope="col" class="px-3 py-3">
                                            {{__("Threat Name")}}
                                        </th>
                                        <th scope="col" class="px-3 py-3">
                                            {{__("Threat Description")}}
                                        </th>
                                        <th scope="col" class="px-3 py-3">
                                            {{__("Total Risk")}}
                                        </th>
                                        <th scope="col" class="px-3 py-3">
                                            {{__("Controls Applied")}}
                                        </th>
                                        <th scope="col" class="px-3 py-3">
                                            {{__("Remaining Risk After Controls")}}
                                        </th>
                                        <th scope="col" class="px-3 py-3">
                                            {{__("Action")}}
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($asset->threats as $threat)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 text-gray-900">
                                            <td class="px-3 py-4">{{$threat->id}}</td>
                                            <td class="px-3 py-4">{{$threat->threat->name}}</td>
                                            <td class="px-3 py-4">{{$threat->threat->description}}</td>
                                            <td style="background-color: {{$threat->totalRiskColor($threat->totalRisk($asset->totalAppreciation()))}}"
                                                class="px-3 py-4">
                                                {{$threat->totalRisk($asset->totalAppreciation())}}</td>
                                            <td class="px-3 py-4">{{$threat->controls()->count()}}</td>

                                            <td style="background-color: {{$threat->totalRiskColor($threat->residual_risk)}}"
                                                class="px-3 py-4">
                                                {{$threat->residual_risk}}
                                            </td>
                                            <td class="px-3 py-4">
                                                <a href="{{route("threats.show",$threat->threat->id)}}"
                                                   class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                                                   target="_blank">
                                                    {{__("View")}}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach

                                    </tbody>
                                </table>
                            </div>

                        </div>
                        <div class="hidden p-4" id="external_enrichment" role="tabpanel"
                             aria-labelledby="external-enrichment-tab">
                            <div class="space-y-6">
                                <div class="grid gap-4 md:grid-cols-4">
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Linked Discovered Hosts') }}</div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $externalExposure['linked_hosts_count'] }}</div>
                                        <div class="mt-1 text-sm text-gray-500">{{ __('Hosts from the attack surface pipeline currently linked to this asset.') }}</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Latest Enrichment') }}</div>
                                        <div class="mt-2 text-sm font-semibold text-gray-900">
                                            {{ $latestRun ? \Illuminate\Support\Str::of((string) $latestRun->status)->replace('_', ' ')->title() : __('No enrichment recorded') }}
                                        </div>
                                        <div class="mt-1 text-sm text-gray-500">
                                            {{ $latestRun?->synced_at?->diffForHumans() ?? __('No successful enrichment yet') }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Observed Open Ports') }}</div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ count($externalExposure['open_ports'] ?? []) }}</div>
                                        <div class="mt-1 text-sm text-gray-500">
                                            {{ !empty($externalExposure['open_ports']) ? collect($externalExposure['open_ports'])->take(8)->implode(', ') : __('No normalized ports yet') }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Observed Vulnerabilities') }}</div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ count($vulnerabilities) }}</div>
                                        <div class="mt-1 text-sm text-gray-500">
                                            {{ !empty($providers) ? collect($providers)->map(fn ($provider) => $assetExternalExposureService->providerLabel((string) $provider))->implode(', ') : __('No providers observed yet') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ __('External Exposure Summary') }}</h3>
                                            <p class="mt-1 text-sm text-gray-500">{{ __('Normalized enrichment data consolidated from the discovered hosts linked to this asset.') }}</p>
                                        </div>
                                        @if($latestRun && filled($latestRun->error))
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">{{ __('Latest run has an error') }}</span>
                                        @endif
                                    </div>

                                    <dl class="mt-4 grid gap-4 md:grid-cols-3">
                                        <div>
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Operating System') }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ filled(data_get($technicalProfile, 'operating_system')) ? data_get($technicalProfile, 'operating_system') : __('Not observed yet') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Organization') }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ filled(data_get($technicalProfile, 'organization')) ? data_get($technicalProfile, 'organization') : __('Not observed yet') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('ISP / ASN') }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ filled(data_get($technicalProfile, 'isp')) ? data_get($technicalProfile, 'isp') : __('Not observed yet') }}
                                                @if(filled(data_get($technicalProfile, 'asn')))
                                                    <span class="text-gray-500">({{ data_get($technicalProfile, 'asn') }})</span>
                                                @endif
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Hostnames') }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ !empty(data_get($technicalProfile, 'hostnames', [])) ? collect(data_get($technicalProfile, 'hostnames'))->implode(', ') : __('No hostnames observed yet') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Domains') }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ !empty(data_get($technicalProfile, 'domains', [])) ? collect(data_get($technicalProfile, 'domains'))->implode(', ') : __('No domains observed yet') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Reputation Tags') }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ !empty(data_get($technicalProfile, 'reputation.tags', [])) ? collect(data_get($technicalProfile, 'reputation.tags'))->implode(', ') : __('No reputation tags observed yet') }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Linked Discovered Hosts') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('These discovered hosts currently feed enrichment data into this asset.') }}</p>

                                    <div class="mt-4 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Host') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Scope') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Latest Enrichment') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Last Seen') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Action') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                            @forelse($linkedHosts as $host)
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        <div class="font-medium text-gray-900">{{ $host->fqdn ?: $host->ip_address }}</div>
                                                        <div class="text-xs text-gray-500">{{ $host->ip_address }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-700">{{ $host->scope?->name ?? __('Unknown scope') }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ \Illuminate\Support\Str::of((string) ($host->status->value ?? $host->status))->replace('_', ' ')->title() }}</td>
                                                    <td class="px-4 py-3 text-gray-700">
                                                        @if($host->latestEnrichmentRun)
                                                            {{ $assetExternalExposureService->providerLabel((string) $host->latestEnrichmentRun->provider) }}
                                                            <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::of((string) $host->latestEnrichmentRun->status)->replace('_', ' ')->title() }}</div>
                                                        @else
                                                            {{ __('No enrichment recorded') }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-700">{{ $host->last_seen_at?->diffForHumans() ?? __('Not observed yet') }}</td>
                                                    <td class="px-4 py-3">
                                                        @if($host->scope)
                                                            <a href="{{ route('attack-surface-scopes.hosts.show', [$host->scope, $host]) }}"
                                                               class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 inline-block">
                                                                {{ __('View Host') }}
                                                            </a>
                                                        @else
                                                            <span class="text-gray-500">{{ __('No scope available') }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-4 py-4 text-gray-500">{{ __('No discovered hosts are linked to this asset yet.') }}</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="grid gap-6 xl:grid-cols-2">
                                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Open Services and Versions') }}</h3>
                                        <div class="mt-4 overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Port') }}</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Protocol') }}</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Service') }}</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Product') }}</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Version') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 bg-white">
                                                @forelse($services as $service)
                                                    <tr>
                                                        <td class="px-4 py-3 text-gray-900">{{ $service['port'] ?? __('Not observed') }}</td>
                                                        <td class="px-4 py-3 text-gray-700">{{ $service['protocol'] ?? __('Not observed') }}</td>
                                                        <td class="px-4 py-3 text-gray-700">{{ $service['service'] ?? __('Not observed') }}</td>
                                                        <td class="px-4 py-3 text-gray-700">{{ $service['product'] ?? __('Not observed') }}</td>
                                                        <td class="px-4 py-3 text-gray-700">{{ $service['version'] ?? __('Not observed') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="px-4 py-4 text-gray-500">{{ __('No service details were normalized for this asset yet.') }}</td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                      <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Detected Technologies') }}</h3>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @forelse($technologies as $technology)
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">{{ $technology }}</span>
                                            @empty
                                                <span class="text-sm text-gray-500">{{ __('No technologies were normalized for this asset yet.') }}</span>
                                            @endforelse
                                        </div>

                                        <div class="mt-6">
                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-600">{{ __('TLS Certificates') }}</h4>
                                            <div class="mt-3 space-y-3">
                                                @forelse($certificates as $certificate)
                                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                                                        <div><span class="font-semibold text-gray-900">{{ __('Subject') }}:</span> {{ data_get($certificate, 'subject', __('Not observed')) }}</div>
                                                        <div><span class="font-semibold text-gray-900">{{ __('Issuer') }}:</span> {{ data_get($certificate, 'issuer', __('Not observed')) }}</div>
                                                        <div><span class="font-semibold text-gray-900">{{ __('Valid To') }}:</span> {{ data_get($certificate, 'valid_to', __('Not observed')) }}</div>
                                                    </div>
                                                @empty
                                                    <div class="text-sm text-gray-500">{{ __('No certificates were normalized for this asset yet.') }}</div>
                                                @endforelse
                                            </div>
                                        </div>

                                        <div class="mt-6 border-t border-gray-200 pt-6">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-600">{{ __('Observed CPEs') }}</h4>
                                                    <p class="mt-1 text-sm text-gray-500">{{ __('Every observed or inferred CPE candidate is stored, and the strongest representative fingerprint is highlighted for the asset.') }}</p>
                                                </div>
                                                @if($asset->detected_cpe)
                                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                                        {{ __('Primary') }}: {{ \Illuminate\Support\Str::of((string) $asset->detected_cpe_confidence)->title() }}
                                                    </span>
                                                @endif
                                            </div>

                                            <dl class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                                <div>
                                                    <dt class="font-medium text-gray-900">{{ __('Detected CPE') }}</dt>
                                                    <dd class="mt-1 text-gray-700 break-all">{{ $asset->detected_cpe ?: __('Not inferred yet') }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="font-medium text-gray-900">{{ __('Source') }}</dt>
                                                    <dd class="mt-1 text-gray-700">{{ $asset->detected_cpe_source ? \Illuminate\Support\Str::of((string) $asset->detected_cpe_source)->replace('_', ' ')->title() : __('Not inferred yet') }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="font-medium text-gray-900">{{ __('Confidence') }}</dt>
                                                    <dd class="mt-1 text-gray-700">{{ $asset->detected_cpe_confidence ? \Illuminate\Support\Str::of((string) $asset->detected_cpe_confidence)->title() : __('Not inferred yet') }}</dd>
                                                </div>
                                            </dl>

                                            @if(!empty($asset->detected_cpe_reasons))
                                                <div class="mt-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ __('Why this CPE was chosen') }}</div>
                                                    <ul class="mt-2 space-y-1 text-sm text-gray-700">
                                                        @foreach($asset->detected_cpe_reasons as $reason)
                                                            <li>{{ $reason }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <div class="mt-4 overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                    <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('CPE') }}</th>
                                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Source') }}</th>
                                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Confidence') }}</th>
                                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Role') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 bg-white">
                                                    @forelse($asset->observedCpes as $observedCpe)
                                                        <tr>
                                                            <td class="px-4 py-3 font-medium text-gray-900 break-all">{{ $observedCpe->cpe }}</td>
                                                            <td class="px-4 py-3 text-gray-700">{{ \Illuminate\Support\Str::of((string) $observedCpe->source)->replace('_', ' ')->title() }}</td>
                                                            <td class="px-4 py-3 text-gray-700">{{ \Illuminate\Support\Str::of((string) $observedCpe->confidence)->title() }}</td>
                                                            <td class="px-4 py-3 text-gray-700">{{ $observedCpe->is_primary ? __('Primary') : __('Candidate') }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="px-4 py-4 text-gray-500">{{ __('No observed CPEs have been inferred for this asset yet.') }}</td>
                                                        </tr>
                                                    @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Known Vulnerabilities') }}</h3>
                                    <div class="mt-4 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('CVE') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Severity') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('CVSS') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('KEV') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('EPSS') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('CWE') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Source') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Description') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                            @forelse($vulnerabilities as $vulnerability)
                                                <tr>
                                                    <td class="px-4 py-3 font-medium text-gray-900">{{ data_get($vulnerability, 'cve', __('Not observed')) }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ data_get($vulnerability, 'severity', __('Not observed')) }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ data_get($vulnerability, 'cvss', __('Not observed')) }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ data_get($vulnerability, 'cisa_kev') ? __('Yes') : __('No') }}</td>
                                                    <td class="px-4 py-3 text-gray-700">
                                                        <div>{{ data_get($vulnerability, 'epss', __('Not observed')) }}</div>
                                                        @if(data_get($vulnerability, 'epss_percentile'))
                                                            <div class="mt-1 text-xs text-gray-500">{{ __('Percentile') }}: {{ data_get($vulnerability, 'epss_percentile') }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-700">{{ data_get($vulnerability, 'cwe', __('Not observed')) }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ data_get($vulnerability, 'intelligence_source', __('Scanner only')) }}</td>
                                                    <td class="px-4 py-3 text-gray-700">
                                                        <div>{{ data_get($vulnerability, 'description', __('No description observed')) }}</div>
                                                        @if(data_get($vulnerability, 'cvss_vector') || collect(data_get($vulnerability, 'references', []))->isNotEmpty())
                                                            <div class="mt-2 space-y-1 text-xs text-gray-500">
                                                                @if(data_get($vulnerability, 'cvss_vector'))
                                                                    <div>{{ __('Vector') }}: {{ data_get($vulnerability, 'cvss_vector') }}</div>
                                                                @endif
                                                                @if(data_get($vulnerability, 'cisa_exploit_added'))
                                                                    <div>{{ __('KEV Added') }}: {{ data_get($vulnerability, 'cisa_exploit_added') }}</div>
                                                                @endif
                                                                @if(data_get($vulnerability, 'epss_date'))
                                                                    <div>{{ __('EPSS Date') }}: {{ data_get($vulnerability, 'epss_date') }}</div>
                                                                @endif
                                                                @if(collect(data_get($vulnerability, 'references', []))->isNotEmpty())
                                                                    <div>{{ __('References') }}: {{ collect(data_get($vulnerability, 'references', []))->take(3)->implode(', ') }}</div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="px-4 py-4 text-gray-500">{{ __('No vulnerabilities were normalized for this asset yet.') }}</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Recent Enrichment Runs') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('Recent external enrichment and scanner executions linked to this asset.') }}</p>
                                    <div class="mt-4 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Run') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Provider') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Host') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Synced At') }}</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Error') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                            @forelse($recentRuns as $run)
                                                <tr>
                                                    <td class="px-4 py-3 text-gray-900">#{{ $run->id }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ $assetExternalExposureService->providerLabel((string) $run->provider) }}</td>
                                                    <td class="px-4 py-3 text-gray-700">
                                                        {{ $run->discoveredHost?->fqdn ?: $run->discoveredHost?->ip_address ?: __('Unknown host') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-700">{{ \Illuminate\Support\Str::of((string) $run->status)->replace('_', ' ')->title() }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ $run->synced_at?->diffForHumans() ?? __('Not synced') }}</td>
                                                    <td class="px-4 py-3 text-gray-700">{{ filled($run->error) ? $run->error : __('None') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-4 py-4 text-gray-500">{{ __('No enrichment runs are linked to this asset yet.') }}</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hidden p-4" id="logs" role="tabpanel"
                             aria-labelledby="logs">
                            @livewire("asset-logs",["asset"=>$asset])

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const params = new URLSearchParams(window.location.search);
            const requestedTab = params.get('tab') || window.location.hash.replace('#', '');

            if (requestedTab !== 'external_enrichment') {
                return;
            }

            const trigger = document.querySelector('#external-enrichment-tab');

            if (trigger) {
                trigger.click();
            }
        });
    </script>
</x-app-layout>
