<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Annual Cyber Risk Assessment Report - CNCS') }} - {{ $year }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background-color: #F7F5F0;
            color: #1C1C1E;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* === CAPA === */
        .cover {
            background-color: #0B1F3A;
            color: #ffffff;
            padding: 60px;
            height: 900px;
            position: relative;
            box-sizing: border-box;
        }
        .cover-top {
            width: 100%;
            margin-bottom: 150px;
        }
        .logo-mark {
            border: 2px solid #B8972A;
            color: #B8972A;
            padding: 10px;
            font-size: 14px;
            display: inline-block;
            margin-right: 15px;
            font-family: monospace;
            font-weight: bold;
        }
        .cover-org {
            display: inline-block;
            font-size: 11px;
            color: rgba(255,255,255,0.6);
            letter-spacing: 2px;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .badge-restrito {
            float: right;
            border: 1px solid #9B1D1D;
            color: #EF7070;
            font-size: 9px;
            padding: 5px 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-family: monospace;
        }
        .cover-eyebrow {
            color: #B8972A;
            font-size: 11px;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 25px;
        }
        .cover-title {
            font-family: 'Times New Roman', serif;
            font-size: 46px;
            font-weight: bold;
            line-height: 1.1;
            margin-bottom: 15px;
            margin-top: 0;
        }
        .cover-subtitle {
            font-size: 16px;
            color: rgba(255,255,255,0.6);
        }
        .cover-year {
            font-family: monospace;
            font-size: 72px;
            margin-top: 80px;
            border-left: 3px solid #B8972A;
            padding-left: 20px;
            line-height: 1;
        }
        .cover-footer {
            position: absolute;
            bottom: 60px;
            left: 60px;
            right: 60px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            font-family: monospace;
        }

        .page-break { page-break-after: always; }

        /* === CONTEÚDO === */
        .content-container {
            padding: 50px 60px;
            background-color: #ffffff;
        }
        .section {
            margin-bottom: 35px;
        }
        h2 {
            font-family: 'Times New Roman', serif;
            font-size: 18px;
            color: #0B1F3A;
            border-bottom: 2px solid #0B1F3A;
            padding-bottom: 8px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .section-num {
            color: #B8972A;
            font-size: 14px;
            display: inline-block;
            margin-right: 8px;
            font-family: monospace;
        }
        .callout {
            background-color: #FDFCF9;
            border-left: 3px solid #0B1F3A;
            padding: 14px 18px;
            margin: 12px 0;
            font-size: 12px;
        }
        .callout-gold {
            border-left-color: #B8972A;
            background-color: #FDFBF4;
        }

        /* Grelha de KPIs */
        table.kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table.kpi-table td {
            width: 25%;
            border: 1px solid #D1CCB8;
            background: #ffffff;
            text-align: center;
            padding: 20px 10px;
        }
        .kpi-val {
            font-family: 'Times New Roman', serif;
            font-size: 28px;
            font-weight: bold;
            color: #0B1F3A;
            margin-bottom: 5px;
        }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6B7280;
            letter-spacing: 1px;
            font-weight: bold;
        }
        .kpi-delta {
            font-family: monospace;
            font-size: 10px;
            color: #2D6B2D;
            margin-top: 5px;
        }

        /* Tabelas Normais */
        table.data-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11px; }
        table.data-table th, table.data-table td { padding: 12px 10px; border-bottom: 1px solid #D1CCB8; text-align: left; vertical-align: top;}
        table.data-table th { background-color: #0B1F3A; color: white; text-transform: uppercase; font-size: 9px; letter-spacing: 1px;}
        .status-badge { background-color: #EEF7EE; color: #2D6B2D; border: 1px solid #B5D9B5; padding: 3px 8px; font-size: 9px; border-radius: 2px; font-family: monospace;}
        .status-badge-alert { background-color: #FEF2F2; color: #9B1D1D; border: 1px solid #FCA5A5; padding: 3px 8px; font-size: 9px; border-radius: 2px; font-family: monospace;}

        /* Lista de Recomendações */
        table.rec-table { width: 100%; margin-bottom: 15px; border-bottom: 1px solid #D1CCB8; padding-bottom: 15px; }
        .rec-icon-cell { width: 40px; vertical-align: top; }
        .rec-icon { background-color: #0B1F3A; color: white; width: 28px; height: 28px; text-align: center; line-height: 28px; display: inline-block; font-size: 10px; font-family: monospace;}
        .rec-title { font-weight: bold; color: #0B1F3A; font-size: 12px; margin-bottom: 4px;}
        .priority-chip { display: inline-block; font-size: 8px; padding: 2px 7px; margin-left: 8px; text-transform: uppercase; border: 1px solid #FCA5A5; color: #9B1D1D; background-color: #FEF2F2; vertical-align: middle; font-family: monospace;}
        .chip-med { border: 1px solid #E2CC88; color: #7C5A0A; background-color: #FDFBF0; }
        .chip-low { border: 1px solid #B5D9B5; color: #2D6B2D; background-color: #F0F7F0; }

        /* Assinaturas */
        table.sig-table { width: 100%; margin-top: 50px; border-top: 2px solid #0B1F3A; padding-top: 30px; }
        table.sig-table td { width: 50%; vertical-align: top; }
        .sig-label { font-size: 9px; color: #6B7280; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 35px; display: block; }
        .sig-line { border-bottom: 1px solid #1C1C1E; width: 80%; margin-bottom: 8px; }

        /* Rodapé de Fim de Página */
        .footer-bar { background-color: #0B1F3A; padding: 18px 60px; font-size: 9px; color: rgba(255,255,255,0.4); font-family: monospace;}
        .footer-badge { float: right; border: 1px solid rgba(155, 29, 29, 0.7); color: rgba(239, 112, 112, 0.8); padding: 3px 10px; letter-spacing: 2px; text-transform: uppercase;}

        ul.styled { padding-left: 20px; margin: 10px 0; }
        ul.styled li { margin-bottom: 8px; color: #374151; }
    </style>
</head>
<body>

<div class="cover">
    <div class="cover-top">
        <div style="float: left;">
            <span class="logo-mark">ARM</span>
            <span class="cover-org">{{ config('app.name') }}<br>{{ __('institutional_report') }}</span>
        </div>
        <div class="badge-restrito">{{ __('internal_restricted') }}</div>
        <div style="clear: both;"></div>
    </div>

    <div style="margin-top: 100px;">
        <div class="cover-eyebrow">{{ __('cncs') }}</div>
        <h1 class="cover-title">{!! __('annual_cybersecurity_report') !!}</h1>
        <div class="cover-subtitle">{{ __('risk_management') }}</div>
        <div class="cover-year">{{ $year }}</div>
    </div>

    <div class="cover-footer">
        <span style="float: left;">{{ __('ref_prefix') }}: ARM-CNCS-{{ $year }}-001 · {{ __('generated_on') }} {{ now()->format('d/m/Y') }}</span>
        <span style="float: right;">{{ __('version_confidential') }}</span>
    </div>
</div>

<div class="page-break"></div>

<div class="content-container">

    <div class="section">
        <h2><span class="section-num">01</span>{{ __('entity_designation') }}</h2>
        <div class="callout">
            <strong>{{ __('entity_name_label') }}</strong><br>
            <span style="color:#6B7280; font-family:monospace;">{{ config('app.name', 'Nome da Entidade') }}</span>
        </div>
    </div>

    <div class="section">
        <h2><span class="section-num">02</span>{{ __('civil_year') }}</h2>
        <div class="callout callout-gold">
            {{ __('analysis_period') }}: <strong>01 de Janeiro de {{ $year }} a 31 de Dezembro de {{ $year }}</strong> — exercício fiscal completo, conforme o Regime Jurídico da Segurança do Ciberespaço.
        </div>
    </div>

    <div class="section">
        <h2><span class="section-num">03</span>{{ __('security_activities') }}</h2>
        <p>{{ __('security_activities') }}</p>

        <table class="kpi-table">
            <tr>
                <td>
                    <div class="kpi-val">{{ $assets->count() }}</div>
                    <div class="kpi-label">{{ __('critical_assets') }}</div>
                    <div class="kpi-delta">{{ __('kpi_active_management') }}</div>
                </td>
                <td>
                    <div class="kpi-val">{{ $stats['Total'] ?? 0 }}</div>
                    <div class="kpi-label">{{ __('incidents') }}</div>
                    <div class="kpi-delta" style="color: {{ ($stats['Total'] ?? 0) > 0 ? '#9B1D1D' : '#6B7280' }};">
                        {{ ($stats['Total'] ?? 0) > 0 ? __('registered_in_year') : __('none_reported') }}
                    </div>
                </td>
                <td>
                    <div class="kpi-val">ISO</div>
                    <div class="kpi-label">{{ __('framework') }}</div>
                    <div class="kpi-delta" style="color:#6B7280;">27001 + CIS</div>
                </td>
                <td>
                    <div class="kpi-val">100%</div>
                    <div class="kpi-label">{{ __('nvd_coverage') }}</div>
                    <div class="kpi-delta">{{ __('synchronized') }}</div>
                </td>
            </tr>
        </table>

        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:20px; text-transform: uppercase; letter-spacing: 1.5px;">{{ __('activities_performed') }}</p>
        <ul class="styled">
            <li>{{ __('activity_monitoring') }}</li>
            <li>{{ __('activity_review_controls') }}</li>
            <li>{{ __('activity_residual_risk') }}</li>
        </ul>
    </div>

    <div class="section">
        <h2><span class="section-num">04</span>{{ __('incident_statistics') }}</h2>
        <p style="color:#6B7280; font-size:11px;">{{ __('reporting_definition') }}</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="25">{{ __('period') }}</th>
                    <th width="10%" style="text-align:center;">{{ __('incidents') }}</th>
                    <th width="35%">{{ __('typology') }}</th>
                    <th width="15%">{{ __('impact') }}</th>
                    <th width="15%">{{ __('status') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $quarters = [
                        'Q1' => __('q1'),
                        'Q2' => __('q2'),
                        'Q3' => __('q3'),
                        'Q4' => __('q4')
                    ];
                @endphp

                @foreach($quarters as $q => $months)
                    @if(isset($groupedIncidents[$q]) && count($groupedIncidents[$q]) > 0)
                        @foreach($groupedIncidents[$q] as $index => $incident)
                            <tr>
                                @if($index == 0)
                                    <td rowspan="{{ count($groupedIncidents[$q]) }}" style="font-family:monospace; vertical-align: middle;">{{ $q }} · {{ $months }} {{ $year }}</td>
                                    <td rowspan="{{ count($groupedIncidents[$q]) }}" style="text-align:center; font-family:monospace; vertical-align: middle;">{{ $stats[$q] }}</td>
                                @endif
                                <td style="color:#1C1C1E;">
                                    <strong>{{ $incident['name'] ?? __('none') }}</strong><br>
                                    <span style="font-size: 9px; color:#6B7280;">{{ __('registered_at') }} {{ \Carbon\Carbon::parse($incident['date'])->format('d/m/Y') }}</span>
                                </td>
                                <td style="color:#9B1D1D; font-weight:bold;">{{ __('pending') }}</td>
                                <td><span class="status-badge-alert">{{ __('reported') }}</span></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td style="font-family:monospace;">{{ $q }} · {{ $months }} {{ $year }}</td>
                            <td style="text-align:center; font-family:monospace;">0</td>
                            <td style="color:#6B7280;">—</td>
                            <td style="color:#6B7280;">—</td>
                            <td><span class="status-badge">{{ __('no_occurrences') }}</span></td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="callout" style="margin-top:0;">
            <strong>{{ __('executive_summary') }}</strong>
            @if(($stats['Total'] ?? 0) == 0)
                {{ __('no_incidents_summary', ['year' => $year]) }}
            @else
                {!! __('incidents_summary', ['count' => $stats['Total'], 'year' => $year]) !!}
            @endif
        </div>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <h2><span class="section-num">05</span>{{ __('incident_analysis') }}</h2>

        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:15px; margin-bottom: 5px;">{{ __('affected_users') }}</p>
        <div class="callout callout-gold">{{ __('not_applicable') }}</div>

        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:15px; margin-bottom: 5px;">{{ __('incident_duration') }}</p>

        @if(($stats['Total'] ?? 0) == 0)
            <div class="callout callout-gold">{{ __('not_applicable') }} (Nenhum incidente reportado no período).</div>
        @else
            <div class="callout callout-gold" style="padding-left: 35px;">
                <ul style="margin: 0; padding-left: 15px; list-style-type: disc;">
                    @foreach($quarters as $q => $months)
                        @if(isset($groupedIncidents[$q]) && count($groupedIncidents[$q]) > 0)
                            @foreach($groupedIncidents[$q] as $incident)
                                <li style="margin-bottom: 6px;">
                                    <strong>{{ $incident['name'] ?? 'Incidente sem título' }}</strong><br>
                                    <span style="color: #4B5563;">
                                        @if(isset($incident['duration_hours']) && $incident['duration_hours'] !== null)
                                            Tempo de resolução: <strong>{{ round($incident['duration_days']) }} dias</strong> ({{ round($incident['duration_hours']) }} horas no total).
                                        @else
                                            <span style="color:#9B1D1D;">Incidente ainda em aberto (sem data de fecho registada no GLPI).</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif
        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:15px; margin-bottom: 5px;">{{ __('geographic_distribution') }}</p>
        <div class="callout callout-gold">{{ __('local_impact') }}</div>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2><span class="section-num">06</span>{{ __('recommendations') }}</h2>
        <p>{{ __('recommendation_intro') }}</p>

        <div style="margin-top:20px;">
            @php
                $highRiskAssets = collect($assets ?? [])->filter(fn($a) => $a->highestRemainingRisk() > 0)
                                         ->sortByDesc(fn($a) => $a->highestRemainingRisk())
                                         ->take(3);
            @endphp

            @if($highRiskAssets->count() > 0)
                @foreach($highRiskAssets as $i => $asset)
                    <table class="rec-table">
                        <tr>
                            <td class="rec-icon-cell"><div class="rec-icon">R{{ $loop->iteration }}</div></td>
                            <td>
                                <div class="rec-title">
                                    {{ $asset->name }}
                                    <span class="priority-chip">{{ __('max_risk') }} {{ $asset->highestRemainingRisk() }}</span>
                                </div>
                                <div style="color:#4B5563;">{{ __('assess_mitigation') }}</div>
                            </td>
                        </tr>
                    </table>
                @endforeach
            @else
                <div class="callout">{{ __('all_assets_mitigated') }}</div>
            @endif

            <table class="rec-table">
                <tr>
                    <td class="rec-icon-cell"><div class="rec-icon" style="background-color:#B8972A;">C</div></td>
                    <td>
                        <div class="rec-title">{{ __('awareness_training') }} <span class="priority-chip chip-med">{{ __('medium') }}</span></div>
                        <div style="color:#4B5563;">{{ __('awareness_training_text') }}</div>
                    </td>
                </tr>
            </table>

            <table class="rec-table" style="border-bottom:none;">
                <tr>
                    <td class="rec-icon-cell"><div class="rec-icon" style="background-color:#2E5F8A;">R</div></td>
                    <td>
                        <div class="rec-title">{{ __('resilience') }} <span class="priority-chip chip-low">{{ __('low') }}</span></div>
                        <div style="color:#4B5563;">{{ __('resilience_text') }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h2><span class="section-num">07</span>{{ __('identified_problems') }}</h2>
        <div class="callout callout-gold">{{ __('monitoring_continuous') }}</div>
    </div>

    <div class="section">
        <h2><span class="section-num">08</span>{{ __('additional_information') }}</h2>
        <p>{{ __('compliance_statement') }}</p>
        <div class="callout">
            <strong>{{ __('normative_references') }}</strong> ISO/IEC 27001:2022 · CIS Controls v8 · NIST CSF 2.0 · Diretiva NIS2 (transposta)
        </div>
    </div>

    <div style="page-break-inside: avoid;">
        <table class="sig-table">
            <tr>
                <td>
                    <span class="sig-label">{{ __('report_date') }}</span>
                    <strong style="font-family:monospace; font-size:14px; color:#1C1C1E;">{{ now()->format('d / m / Y') }}</strong><br>
                    <span style="font-size:10px; color:#6B7280; margin-top:5px; display:inline-block;">Lisboa, Portugal</span>
                </td>
                <td>
                    <span class="sig-label">{{ __('security_officer') }}</span>
                    <div class="sig-line"></div>
                    <strong style="color:#1C1C1E; font-size:12px;">{{ __('authorized_signature') }}</strong><br>
                    <span style="font-size:10px; color:#6B7280;">{{ __('digital_signature') }}</span>
                </td>
            </tr>
        </table>
    </div>

</div>

<div class="footer-bar">
    <span style="float: left;">{{ __('generated_by') }} · {{ now()->format('d/m/Y') }} às {{ now()->format('H:i') }}</span>
    <span class="footer-badge">{{ __('internal_restricted') }}</span>
    <div style="clear:both;"></div>
</div>

</body>
</html>
