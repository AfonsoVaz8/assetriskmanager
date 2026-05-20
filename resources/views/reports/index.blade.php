<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{__("Reports")}}</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab"
                            data-tabs-toggle="#tabs" role="tablist">
                            <li class="mr-2" role="presentation">
                                <button
                                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="dashboard-tab" data-tabs-target="#asset_list" type="button" role="tab"
                                        aria-controls="asset_list" aria-selected="false">{{__("Asset List")}}
                                </button>
                            </li>
                            <li role="presentation">
                                <button class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="annual-reports-tab" data-tabs-target="#annual_reports" type="button" role="tab"
                                        aria-controls="annual_reports" aria-selected="false">{{__("Stored Reports")}}
                                </button>
                            </li>
                            <li class="mr-2" role="presentation">
                                <button
                                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="settings-tab" data-tabs-target="#risk_map" type="button" role="tab"
                                        aria-controls="risk_map" aria-selected="false">{{__("Risk Map")}}
                                </button>
                            </li>
                            <li role="presentation">
                                <button
                                        class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        id="contacts-tab" data-tabs-target="#dependency_graph" type="button" role="tab"
                                        aria-controls="dependency_graph"
                                        aria-selected="false">{{__("Dependency Graph")}}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div id="tabs">
                        <div class="hidden p-4" id="asset_list" role="tabpanel"
                             aria-labelledby="asset_list-tab">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-separate">
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
                                        {{__("Type")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("IP")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("FQDN")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Integrity Appreciation")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Availability Appreciation")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Confidentiality Appreciation")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Total Appreciation")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Risk Score")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Action")}}
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($assets as $asset)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 text-gray-900">
                                        <td class="px-6 py-4">{{$asset->id}}</td>
                                        <td class="px-6 py-4">{{$asset->name}}</td>
                                        <td class="px-6 py-4">{{$asset->description}}</td>
                                        <td class="px-6 py-4">{{$asset->type->name}}</td>
                                        <td class="px-6 py-4">{{$asset->ip_address}}</td>
                                        <td class="px-6 py-4">{{$asset->fqdn}}</td>
                                        <td
                                                style="background-color: {{$asset->color($asset->integrity_appreciation)}}"
                                                class="px-6 py-4">{{$asset->integrity_appreciation}}</td>
                                        <td
                                                style="background-color: {{$asset->color($asset->availability_appreciation)}}"
                                                class="px-6 py-4">{{$asset->availability_appreciation}}</td>
                                        <td
                                                style="background-color: {{$asset->color($asset->confidentiality_appreciation)}}"
                                                class="px-6 py-4">{{$asset->confidentiality_appreciation}}</td>
                                        <td
                                                style="background-color: {{$asset->color($asset->totalAppreciation())}}"
                                                class="px-6 py-4">{{$asset->totalAppreciation()}}</td>
                                        <td
                                                style="background-color: {{App\Models\AssetThreat::totalRiskColor($asset->highestRemainingRisk())}}"
                                                class="px-6 py-4">{{$asset->highestRemainingRisk()}}</td>
                                        <td class="px-6 py-4">
                                            @can("update",$asset)
                                                <a href="{{route("assets.edit",$asset->id)}}"
                                                   class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                                    {{__("Manage")}}
                                                </a>
                                            @else
                                                <a href="{{route("assets.show",$asset->id)}}"
                                                   class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                                    {{__("View")}}
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            <div class="flex justify-center">
                                <a class="inline-flex items-center h-10 px-5 m-2 text-sm text-blue-100 transition-colors duration-150 bg-blue-700 rounded-lg focus:shadow-outline hover:bg-blue-800"
                                   href="{{route("reports","export=asset_list")}}" target="_blank">{{__("Export")}}</a>
                                <a class="inline-flex items-center h-10 px-5 m-2 text-sm text-blue-100 transition-colors duration-150 bg-blue-700 rounded-lg focus:shadow-outline hover:bg-blue-800"
                                    href="{{route("reports","export=cncs_save")}}" target="_blank">{{__("Export CNCS")}}</a>
                            </div>
                        </div>
                        <div class="hidden p-4" id="risk_map" role="tabpanel"
                             aria-labelledby="risk_map-tab">

                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-separate">
                                <thead
                                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Asset ID")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Asset Name")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Threat")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Availability Impact")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Confidentiality Impact")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Integrity Impact")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Probability")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Asset Appreciation")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Total Risk")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Controls")}}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{__("Remaining Risk After Controls")}}
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($assets as $asset)
                                    @foreach($asset->threats as $threat)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 text-gray-900">
                                            <td class="px-6 py-4">{{$asset->id}}</td>
                                            <td class="px-6 py-4">{{$asset->name}}</td>
                                            <td class="px-6 py-4">{{$threat->threat->name}}</td>
                                            <td style="background-color: {{$threat->color($threat->availability_impact)}}"
                                                class="px-3 py-4">{{$threat->availability_impact}}</td>
                                            <td style="background-color: {{$threat->color($threat->confidentiality_impact)}}"
                                                class="px-3 py-4">{{$threat->confidentiality_impact}}</td>
                                            <td style="background-color: {{$threat->color($threat->integrity_impact)}}"
                                                class="px-3 py-4">{{$threat->integrity_impact}}</td>
                                            <td style="background-color: {{$threat->color($threat->probability)}}"
                                                class="px-3 py-4">{{$threat->probability}}</td>
                                            <td style="background-color: {{$asset->color($asset->totalAppreciation())}}"
                                                class="px-3 py-4">{{$asset->totalAppreciation()}}</td>
                                            <td style="background-color: {{$threat->totalRiskColor($threat->totalRisk($asset->totalAppreciation()))}}"
                                                class="px-3 py-4">{{$threat->totalRisk($asset->totalAppreciation())}}</td>
                                            <td class="px-3 py-4">{{$threat->controls()->count()}}</td>
                                            <td style="background-color: {{$threat->totalRiskColor($threat->residual_risk)}}"
                                                class="px-3 py-4">{{$threat->residual_risk == 0 ? "" : $threat->residual_risk}}</td>
                                        </tr>

                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                            <div class="flex justify-center gap-2 mt-4">
                                <a class="inline-flex items-center h-10 px-5 text-sm text-blue-100 transition-colors duration-150 bg-blue-700 rounded-lg focus:shadow-outline hover:bg-blue-800"
                                href="{{route("reports","export=asset_list")}}" target="_blank">{{__("Export Excel")}}</a>
                            </div>
                        </div>
                        <div class="hidden p-4" id="dependency_graph"
                             role="tabpanel"
                             aria-labelledby="dependency_graph-tab">
                            <div class="flex justify-center">
                                <button onclick="saveImage()" type="button"
                                        class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                                    {{__("Export")}}
                                </button>
                            </div>
                            <div id="cy" class="h-screen w-screen text-base">

                            </div>

                        </div>
                        <div class="hidden p-4" id="annual_reports" role="tabpanel" aria-labelledby="annual-reports-tab">

                            <form method="GET" action="{{ route('reports') }}" class="mb-6 p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 flex flex-wrap gap-4 items-end">

                                <input type="hidden" name="tab" value="stored_reports">

                                <div>
                                    <label for="filter_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{__("Generation Date")}}</label>
                                    <input type="date" id="filter_date" name="filter_date" value="{{ request('filter_date') }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                <div>
                                    <label for="filter_year" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{__("Year")}}</label>
                                    <input type="number" id="filter_year" name="filter_year" value="{{ request('filter_year') }}" placeholder="Ex: 2024"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                <div>
                                    <label for="filter_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{__("Type")}}</label>
                                    <select id="filter_type" name="filter_type"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">{{__("All")}}</option>
                                        <option value="CNCS" {{ request('filter_type') == 'CNCS' ? 'selected' : '' }}>CNCS</option>
                                        <option value="Cibersegurança" {{ request('filter_type') == 'Cibersegurança' ? 'selected' : '' }}>Cibersegurança</option>
                                    </select>
                                </div>

                                <div class="flex gap-2 ml-auto">
                                    <a href="{{ route('reports', ['tab' => 'stored_reports']) }}" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                                        {{__("Clear")}}
                                    </a>
                                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none">
                                        {{__("Filter")}}
                                    </button>
                                </div>
                            </form>

                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-separate">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">{{__("Year")}}</th>
                                    <th scope="col" class="px-6 py-3">{{__("Type")}}</th>
                                    <th scope="col" class="px-6 py-3">{{__("Generation Date")}}</th>
                                    <th scope="col" class="px-6 py-3">{{__("Action")}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($annualReports as $report)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 text-gray-900">
                                        <td class="px-6 py-4">{{ $report->year }}</td>
                                        <td class="px-6 py-4">{{ $report->type }}</td>
                                        <td class="px-6 py-4">{{ $report->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-6 py-4">
                                            <a href="{{ asset('storage/' . $report->file_path) }}" target="_blank"
                                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800">
                                                {{__("Download")}}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            <div class="mt-4 flex gap-2">
                                <a class="inline-flex items-center h-10 px-5 m-2 text-sm text-blue-100 transition-colors duration-150 bg-blue-700 rounded-lg focus:shadow-outline hover:bg-blue-800"
                                    href="{{route("reports","export=cncs_save")}}" target="_blank">{{__("Generate CNCS Report")}}</a>
                                <a class="inline-flex items-center h-10 px-5 m-2 text-sm text-blue-100 transition-colors duration-150 bg-blue-700 rounded-lg focus:shadow-outline hover:bg-blue-800"
                                    href="{{route("reports","export=cybersecurity_save")}}" target="_blank">{{__("Generate Cyber Report")}}</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push("js")
        <script>

            let imageBlob = null;

            async function saveImage() {
                if (imageBlob != null) {
                    imageBlob = await cy.png({output: "blob-promise", full: true});
                    saveAs(imageBlob, "graph.png");
                }
            }

            window.addEventListener('load', async function () {
                if (typeof cytoscape !== 'undefined') {
                    cytoscape.use(dagre);
                    let cy = (window.cy = cytoscape({
                        container: document.getElementById("cy"),

                        boxSelectionEnabled: false,
                        autounselectify: true,

                        layout: {
                            name: "dagre"
                        },

                        style: [
                            {
                                selector: "node",
                                style: {
                                    "label": "data(data)",
                                    "text-valign": "center",
                                    "text-halign": "center",
                                    "shape": "rectangle",
                                    "border-width": 2,
                                    "border-color": "black",
                                    "border-style": "dotted",
                                    "color": "black",
                                    "text-background-padding": "data(width)",
                                    "background-color": "data(color)",
                                    "text-wrap": "wrap",
                                    'width': "data(width)",
                                    'height': "data(height)",
                                }
                            },
                            {
                                selector: "edge",
                                style: {
                                    "curve-style": "bezier",
                                    width: 4,
                                    "target-arrow-shape": "triangle",
                                    "line-color": "#9dbaea",
                                    "target-arrow-color": "#9dbaea"
                                }
                            }
                        ],
                        elements: {
                            nodes: @json($nodes_array),
                            edges: @json($edges_array)
                        }
                    }));
                    cy.resize();
                    @if(count($nodes_array)>0)
                        imageBlob = await cy.png({output: "blob-promise", full: true});
                    @endif
                    cy.on('tap', 'node', function () {
                        try {
                            window.open(this.data('link'));
                        } catch (e) {
                            window.location.href = this.data('link');
                        }
                    });
                }
            });

            window.addEventListener('load', function() {
                const urlParams = new URLSearchParams(window.location.search);

                if (urlParams.has('tab') && urlParams.get('tab') === 'stored_reports') {
                    setTimeout(function() {
                        const tabBtn = document.getElementById('annual-reports-tab');
                        if (tabBtn) {
                            tabBtn.click();
                        }
                    }, 150);
                }
            });
        </script>
    @endpush
</x-app-layout>
