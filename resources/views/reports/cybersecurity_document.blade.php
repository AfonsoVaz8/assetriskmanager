<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório Anual CNCS - {{ $year }}</title>
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
            height: 900px; /* Preenche a primeira folha A4 no PDF */
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
            <span class="cover-org">Asset Risk Manager<br>Relatório Institucional</span>
        </div>
        <div class="badge-restrito">INTERNO · RESTRITO</div>
        <div style="clear: both;"></div>
    </div>

    <div style="margin-top: 100px;">
        <div class="cover-eyebrow">Centro Nacional de Cibersegurança — CNCS</div>
        <h1 class="cover-title">Relatório Anual<br>de Cibersegurança</h1>
        <div class="cover-subtitle">Gestão de Risco e Segurança da Informação</div>
        <div class="cover-year">{{ $year }}</div>
    </div>

    <div class="cover-footer">
        <span style="float: left;">REF: ARM-CNCS-{{ $year }}-001 · Gerado em {{ now()->format('d/m/Y') }}</span>
        <span style="float: right;">Versão 1.0 · Confidencial</span>
    </div>
</div>

<div class="page-break"></div>

<div class="content-container">

    <div class="section">
        <h2><span class="section-num">01</span>Designação da Entidade</h2>
        <div class="callout">
            <strong>Nome da Entidade:</strong><br>
            <span style="color:#6B7280; font-family:monospace;">{{ config('app.name', 'Nome da Entidade') }}</span>
        </div>
    </div>

    <div class="section">
        <h2><span class="section-num">02</span>Ano Civil e Período de Reporte</h2>
        <div class="callout callout-gold">
            Período em análise: <strong>01 de Janeiro de {{ $year }} a 31 de Dezembro de {{ $year }}</strong> — exercício fiscal completo, conforme o Regime Jurídico da Segurança do Ciberespaço.
        </div>
    </div>

    <div class="section">
        <h2><span class="section-num">03</span>Descrição das Principais Atividades de Segurança</h2>
        <p>Durante o exercício de {{ $year }}, a organização consolidou a sua estratégia de cibersegurança e gestão de risco tecnológico, mantendo a postura de segurança alinhada com os referenciais internacionais aplicáveis.</p>

        <table class="kpi-table">
            <tr>
                <td>
                    <div class="kpi-val">{{ $assets->count() }}</div>
                    <div class="kpi-label">Ativos Críticos</div>
                    <div class="kpi-delta">↑ em gestão ativa</div>
                </td>
                <td>
                    <div class="kpi-val">0</div>
                    <div class="kpi-label">Incidentes CIA</div>
                    <div class="kpi-delta" style="color:#6B7280;">Nenhum reportado</div>
                </td>
                <td>
                    <div class="kpi-val">ISO</div>
                    <div class="kpi-label">Framework</div>
                    <div class="kpi-delta" style="color:#6B7280;">27001 + CIS</div>
                </td>
                <td>
                    <div class="kpi-val">100%</div>
                    <div class="kpi-label">Cobertura NVD</div>
                    <div class="kpi-delta">Sincronizado</div>
                </td>
            </tr>
        </table>

        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:20px; text-transform: uppercase; letter-spacing: 1.5px;">Atividades Desenvolvidas</p>
        <ul class="styled">
            <li>Monitorização e mapeamento contínuo de vulnerabilidades através de sincronização com catálogos internacionais NVD/CVE.</li>
            <li>Revisão periódica dos controlos de segurança aplicados, com alinhamento às melhores práticas da indústria (ISO/IEC 27001 e CIS Controls).</li>
            <li>Avaliação e tratamento do Risco Residual para assegurar conformidade com o apetite ao risco da Administração.</li>
        </ul>
    </div>

    <div class="section">
        <h2><span class="section-num">04</span>Estatística Trimestral de Incidentes de Segurança</h2>
        <p style="color:#6B7280; font-size:11px;">São reportados exclusivamente incidentes que representem quebra efetiva da Confidencialidade, Integridade ou Disponibilidade (CIA).</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="30%">Período</th>
                    <th width="15%" style="text-align:center;">Nº Inc.</th>
                    <th width="25%">Tipologia / Vetor</th>
                    <th width="15%">Impacto CIA</th>
                    <th width="15%">Estado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-family:monospace;">Q1 · Jan — Mar {{ $year }}</td>
                    <td style="text-align:center; font-family:monospace;">0</td>
                    <td style="color:#6B7280;">—</td>
                    <td style="color:#6B7280;">—</td>
                    <td><span class="status-badge">Encerrado</span></td>
                </tr>
                <tr>
                    <td style="font-family:monospace;">Q2 · Abr — Jun {{ $year }}</td>
                    <td style="text-align:center; font-family:monospace;">0</td>
                    <td style="color:#6B7280;">—</td>
                    <td style="color:#6B7280;">—</td>
                    <td><span class="status-badge">Encerrado</span></td>
                </tr>
                <tr>
                    <td style="font-family:monospace;">Q3 · Jul — Set {{ $year }}</td>
                    <td style="text-align:center; font-family:monospace;">0</td>
                    <td style="color:#6B7280;">—</td>
                    <td style="color:#6B7280;">—</td>
                    <td><span class="status-badge">Encerrado</span></td>
                </tr>
                <tr>
                    <td style="font-family:monospace;">Q4 · Out — Dez {{ $year }}</td>
                    <td style="text-align:center; font-family:monospace;">0</td>
                    <td style="color:#6B7280;">—</td>
                    <td style="color:#6B7280;">—</td>
                    <td><span class="status-badge">Encerrado</span></td>
                </tr>
            </tbody>
        </table>

        <div class="callout" style="margin-top:0;">
            <strong>Sumário executivo:</strong> Nenhum incidente de segurança com impacto na tríade CIA foi identificado ou reportado durante o exercício de {{ $year }}.
        </div>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <h2><span class="section-num">05</span>Análise Agregada de Incidentes (Impacto Relevante)</h2>
        
        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:15px; margin-bottom: 5px;">5.1 — Utilizadores afetados</p>
        <div class="callout callout-gold">Não aplicável — ausência de incidentes no período de reporte.</div>

        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:15px; margin-bottom: 5px;">5.2 — Duração dos incidentes</p>
        <div class="callout callout-gold">Não aplicável — ausência de incidentes no período de reporte.</div>

        <p style="font-weight:bold; font-size:12px; color:#1A3A5C; margin-top:15px; margin-bottom: 5px;">5.3 — Distribuição geográfica e impacto transfronteiriço</p>
        <div class="callout callout-gold">Não aplicável — ausência de incidentes no período de reporte.</div>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2><span class="section-num">06</span>Recomendações para Melhoria da Postura</h2>
        <p>Com base na avaliação de risco contínua extraída do <em>Asset Risk Manager</em>, identificam-se os seguintes vetores prioritários para intervenção:</p>

        <div style="margin-top:20px;">
            @php 
                $highRiskAssets = $assets->filter(fn($a) => $a->highestRemainingRisk() > 0)
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
                                    <span class="priority-chip">Risco Máx: {{ $asset->highestRemainingRisk() }}</span>
                                </div>
                                <div style="color:#4B5563;">Avaliar adoção de controlos mitigadores adicionais ou obter aprovação formal para aceitação do risco residual.</div>
                            </td>
                        </tr>
                    </table>
                @endforeach
            @else
                <div class="callout">Todos os ativos tecnológicos apresentam risco residual mitigado e/ou dentro dos parâmetros aceites formalmente.</div>
            @endif

            <table class="rec-table">
                <tr>
                    <td class="rec-icon-cell"><div class="rec-icon" style="background-color:#B8972A;">C</div></td>
                    <td>
                        <div class="rec-title">Consciencialização e Formação <span class="priority-chip chip-med">Média</span></div>
                        <div style="color:#4B5563;">Executar campanhas anuais de simulação de Phishing e formação em higiene cibernética para todos os colaboradores.</div>
                    </td>
                </tr>
            </table>

            <table class="rec-table" style="border-bottom:none;">
                <tr>
                    <td class="rec-icon-cell"><div class="rec-icon" style="background-color:#2E5F8A;">R</div></td>
                    <td>
                        <div class="rec-title">Resiliência e Continuidade <span class="priority-chip chip-low">Baixa</span></div>
                        <div style="color:#4B5563;">Revisão semestral do Plano de Resposta a Incidentes (IRP) e verificação da redundância de backups imutáveis.</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h2><span class="section-num">07</span>Problemas Identificados / Medidas</h2>
        <div class="callout callout-gold">Não foram identificados problemas de segurança com origem em incidentes durante o período em análise.</div>
    </div>

    <div class="section">
        <h2><span class="section-num">08</span>Informação Complementar</h2>
        <p>O presente relatório é submetido em cumprimento com o Regime Jurídico da Segurança do Ciberespaço (Lei n.º 46/2018) e as diretrizes do Centro Nacional de Cibersegurança (CNCS).</p>
        <div class="callout">
            <strong>Referências normativas:</strong> ISO/IEC 27001:2022 · CIS Controls v8 · NIST CSF 2.0 · Diretiva NIS2 (transposta)
        </div>
    </div>

    <div style="page-break-inside: avoid;">
        <table class="sig-table">
            <tr>
                <td>
                    <span class="sig-label">Data do relatório</span>
                    <strong style="font-family:monospace; font-size:14px; color:#1C1C1E;">{{ now()->format('d / m / Y') }}</strong><br>
                    <span style="font-size:10px; color:#6B7280; margin-top:5px; display:inline-block;">Lisboa, Portugal</span>
                </td>
                <td>
                    <span class="sig-label">Responsável de Segurança (CISO)</span>
                    <div class="sig-line"></div>
                    <strong style="color:#1C1C1E; font-size:12px;">Assinatura Autorizada</strong><br>
                    <span style="font-size:10px; color:#6B7280;">Selo Digital ou Rubrica Manual</span>
                </td>
            </tr>
        </table>
    </div>

</div>

<div class="footer-bar">
    <span style="float: left;">Gerado eletronicamente pelo sistema Asset Risk Manager · {{ now()->format('d/m/Y') }} às {{ now()->format('H:i') }}</span>
    <span class="footer-badge">INTERNO / RESTRITO</span>
    <div style="clear:both;"></div>
</div>

</body>
</html>