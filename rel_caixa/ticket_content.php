<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
include('addons.class.php');

// VERIFICA SE O USUARIO ESTA LOGADO --------------------------------------------------------------
session_name('mka');
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['mka_logado']) && !isset($_SESSION['MKA_Logado'])) exit('Acesso negado... <a href="/admin/login.php">Fazer Login</a>');
// VERIFICA SE O USUARIO ESTA LOGADO --------------------------------------------------------------

$manifestTitle = $Manifest->{'name'} ?? '';
$manifestVersion = $Manifest->{'version'} ?? '';
?>

<?php
// ARQUIVO: ticket_content.php
// Este arquivo contém o conteúdo da aba de Ticket Médio (ARPU - Average Revenue Per User)

// Filtros
?>

<div class="filter-section">
    <div class="filter-group">
        <label for="ticket_ano">📅 Ano</label>
        <select id="ticket_ano" name="ticket_ano" onchange="atualizarTicket()">
            <?php
            $ano_atual = date('Y');
            $ticket_ano = isset($_GET['ticket_ano']) ? intval($_GET['ticket_ano']) : $ano_atual;
            for ($i = $ano_atual; $i >= $ano_atual - 10; $i--) {
                $selected = ($i == $ticket_ano) ? 'selected' : '';
                echo "<option value='$i' $selected>$i</option>";
            }
            ?>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="ticket_mes">📆 Mês</label>
        <select id="ticket_mes" name="ticket_mes" onchange="atualizarTicket()">
            <?php
            $ticket_mes = isset($_GET['ticket_mes']) ? intval($_GET['ticket_mes']) : 0;
            $meses_lista = [
                0 => 'Todos os Meses',
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
            ];
            foreach ($meses_lista as $num => $nome) {
                $selected = ($num == $ticket_mes) ? 'selected' : '';
                echo "<option value='$num' $selected>$nome</option>";
            }
            ?>
        </select>
    </div>
    
    <!--<button class="btn btn-primary" onclick="atualizarTicket()">🔄 Atualizar</button>-->
</div>

<?php
// Buscar dados do banco para calcular ticket médio - OTIMIZADO
$ticket_ano = isset($_GET['ticket_ano']) ? intval($_GET['ticket_ano']) : date('Y');
$ticket_mes = isset($_GET['ticket_mes']) ? intval($_GET['ticket_mes']) : 0;

// Arrays para armazenar dados
$dados_ticket_medio = [];
$labels_ticket = [];

if ($ticket_mes > 0) {
    // CÁLCULO POR MÊS ESPECÍFICO - Ticket médio diário - OTIMIZADO
    $query_diario = "
        SELECT 
            DAY(data) as dia,
            SUM(entrada) as receita_total,
            COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_pagamentos
        FROM sis_caixa
        WHERE YEAR(data) = $ticket_ano 
        AND MONTH(data) = $ticket_mes
        AND entrada > 0
        GROUP BY DAY(data)
        ORDER BY dia ASC
    ";
    
    $result_diario = mysqli_query($link, $query_diario);
    
    $dados_por_dia = [];
    while ($row = mysqli_fetch_assoc($result_diario)) {
        $dados_por_dia[$row['dia']] = [
            'receita' => floatval($row['receita_total']),
            'pagamentos' => intval($row['total_pagamentos'])
        ];
    }
    
    $dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $ticket_mes, $ticket_ano);
    
    for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
        $labels_ticket[] = $dia;
        
        if (isset($dados_por_dia[$dia])) {
            $receita = $dados_por_dia[$dia]['receita'];
            $pagamentos = $dados_por_dia[$dia]['pagamentos'];
            $ticket_medio_dia = $pagamentos > 0 ? $receita / $pagamentos : 0;
        } else {
            $ticket_medio_dia = 0;
        }
        
        $dados_ticket_medio[] = round($ticket_medio_dia, 2);
    }
    
    $meses_nomes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $periodo_nome = $meses_nomes[$ticket_mes] . ' de ' . $ticket_ano;
    
} else {
    // CÁLCULO ANUAL - Ticket médio mensal - OTIMIZADO
    $meses_nomes_curtos = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    
    $query_mensal = "
        SELECT 
            MONTH(data) as mes,
            SUM(entrada) as receita_total,
            COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_pagamentos
        FROM sis_caixa
        WHERE YEAR(data) = $ticket_ano
        AND entrada > 0
        GROUP BY MONTH(data)
        ORDER BY mes ASC
    ";
    
    $result_mensal = mysqli_query($link, $query_mensal);
    
    $dados_por_mes = [];
    while ($row = mysqli_fetch_assoc($result_mensal)) {
        $dados_por_mes[$row['mes']] = [
            'receita' => floatval($row['receita_total']),
            'pagamentos' => intval($row['total_pagamentos'])
        ];
    }
    
    for ($mes = 1; $mes <= 12; $mes++) {
        $labels_ticket[] = $meses_nomes_curtos[$mes - 1];
        
        if (isset($dados_por_mes[$mes])) {
            $receita = $dados_por_mes[$mes]['receita'];
            $pagamentos = $dados_por_mes[$mes]['pagamentos'];
            $ticket_medio_mes = $pagamentos > 0 ? $receita / $pagamentos : 0;
        } else {
            $ticket_medio_mes = 0;
        }
        
        $dados_ticket_medio[] = round($ticket_medio_mes, 2);
    }
    
    $periodo_nome = $ticket_ano;
}

// Calcular estatísticas
$valores_validos = array_filter($dados_ticket_medio, function($v) { return $v > 0; });
$ticket_medio_periodo = count($valores_validos) > 0 ? array_sum($valores_validos) / count($valores_validos) : 0;
$ticket_minimo = count($valores_validos) > 0 ? min($valores_validos) : 0;
$ticket_maximo = count($valores_validos) > 0 ? max($valores_validos) : 0;

// Total geral para o período - OTIMIZADO
$query_total_periodo = $ticket_mes > 0 
    ? "SELECT 
        SUM(entrada) as receita_total,
        COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_pagamentos
       FROM sis_caixa
       WHERE YEAR(data) = $ticket_ano 
       AND MONTH(data) = $ticket_mes
       AND entrada > 0"
    : "SELECT 
        SUM(entrada) as receita_total,
        COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_pagamentos
       FROM sis_caixa
       WHERE YEAR(data) = $ticket_ano
       AND entrada > 0";

$result_total = mysqli_query($link, $query_total_periodo);
$dados_total = mysqli_fetch_assoc($result_total);
$receita_total_periodo = floatval($dados_total['receita_total'] ?? 0);
$total_pagamentos_periodo = intval($dados_total['total_pagamentos'] ?? 0);
$ticket_medio_real = $total_pagamentos_periodo > 0 ? $receita_total_periodo / $total_pagamentos_periodo : 0;

// Converter para JSON
$json_labels_ticket = json_encode($labels_ticket);
$json_dados_ticket = json_encode($dados_ticket_medio);
?>

<!-- Cards de Ticket Médio -->
<div class="stats-grid">
    <div class="stat-card ticket-card">
        <div class="stat-header">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-label">Ticket Médio - <?php echo $periodo_nome; ?></div>
                <div class="stat-value">R$ <?php echo number_format($ticket_medio_real, 2, ',', '.'); ?></div>
                <div class="stat-trend">Receita média por cliente</div>
            </div>
        </div>
    </div>

    <div class="stat-card receita-card">
        <div class="stat-header">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-label">Receita Total</div>
                <div class="stat-value">R$ <?php echo number_format($receita_total_periodo, 2, ',', '.'); ?></div>
                <div class="stat-trend">Entradas do período</div>
            </div>
        </div>
    </div>

    <div class="stat-card clientes-card">
        <div class="stat-header">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-label">Total de Pagamentos</div>
                <div class="stat-value"><?php echo number_format($total_pagamentos_periodo, 0, ',', '.'); ?></div>
                <div class="stat-trend">Boletos pagos no período</div>
            </div>
        </div>
    </div>

    <div class="stat-card variacao-card">
        <div class="stat-header">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <div class="stat-label">Variação</div>
                <div class="stat-value">
                    Min: R$ <?php echo number_format($ticket_minimo, 2, ',', '.'); ?>
                    <br><small>Max: R$ <?php echo number_format($ticket_maximo, 2, ',', '.'); ?></small>
                </div>
                <div class="stat-trend">Menor e maior ticket</div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico de Ticket Médio -->
<div class="charts-grid">
    <div class="chart-container" style="grid-column: 1 / -1;">
        <div class="chart-header">
            <div class="chart-title">📊 Evolução do Ticket Médio - <?php echo $periodo_nome; ?></div>
            <div class="chart-subtitle">
                A linha tracejada representa a média do período (R$ <?php echo number_format($ticket_medio_real, 2, ',', '.'); ?>)
            </div>
        </div>
        <div class="chart-wrapper" style="height: 400px;">
            <canvas id="chartTicketMedio"></canvas>
        </div>
    </div>
</div>

<!-- Comparação com Período Anterior -->
<?php
// Calcular período anterior para comparação
if ($ticket_mes > 0) {
    // Comparar com o MESMO MÊS do ANO ANTERIOR
    $mes_anterior = $ticket_mes;
    $ano_anterior = $ticket_ano - 1;
    
    $query_anterior = "
        SELECT 
            SUM(entrada) as receita_total,
            COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_pagamentos
        FROM sis_caixa
        WHERE YEAR(data) = $ano_anterior 
        AND MONTH(data) = $mes_anterior
        AND entrada > 0
    ";
} else {
    // Comparar com ano anterior
    $ano_anterior = $ticket_ano - 1;
    
    $query_anterior = "
        SELECT 
            SUM(entrada) as receita_total,
            COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_pagamentos
        FROM sis_caixa
        WHERE YEAR(data) = $ano_anterior
        AND entrada > 0
    ";
}

$result_anterior = mysqli_query($link, $query_anterior);
$dados_anterior = mysqli_fetch_assoc($result_anterior);
$receita_anterior = floatval($dados_anterior['receita_total'] ?? 0);
$pagamentos_anterior = intval($dados_anterior['total_pagamentos'] ?? 0);
$ticket_anterior = $pagamentos_anterior > 0 ? $receita_anterior / $pagamentos_anterior : 0;

// Calcular variações
$variacao_ticket = $ticket_anterior > 0 ? (($ticket_medio_real - $ticket_anterior) / $ticket_anterior) * 100 : 0;
$variacao_receita = $receita_anterior > 0 ? (($receita_total_periodo - $receita_anterior) / $receita_anterior) * 100 : 0;
$variacao_pagamentos = $pagamentos_anterior > 0 ? (($total_pagamentos_periodo - $pagamentos_anterior) / $pagamentos_anterior) * 100 : 0;

$meses_nomes_completo = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$periodo_anterior_nome = $ticket_mes > 0 ? $meses_nomes_completo[$mes_anterior] . '/' . $ano_anterior : $ano_anterior;

// Análise de tendência
$tendencia = '';
$tendencia_icon = '';
if ($variacao_ticket > 5) {
    $tendencia = '📈 <strong style="color: #10B981;">Tendência Positiva</strong> - Ticket médio cresceu ' . number_format($variacao_ticket, 1, ',', '.') . '% em relação ao ' . ($ticket_mes > 0 ? 'mesmo período do ano passado' : 'ano anterior') . '!';
    $tendencia_icon = '🎉';
} elseif ($variacao_ticket < -5) {
    $tendencia = '📉 <strong style="color: #EF4444;">Tendência Negativa</strong> - Ticket médio caiu ' . number_format(abs($variacao_ticket), 1, ',', '.') . '% em relação ao ' . ($ticket_mes > 0 ? 'mesmo período do ano passado' : 'ano anterior') . '.';
    $tendencia_icon = '⚠️';
} else {
    $tendencia = '➡️ <strong style="color: #3B82F6;">Tendência Estável</strong> - Ticket médio mantido em comparação ao ' . ($ticket_mes > 0 ? 'mesmo período do ano passado' : 'ano anterior') . '.';
    $tendencia_icon = '✅';
}

// Encontrar melhor e pior período
$melhor_periodo = '';
$pior_periodo = '';
$melhor_valor = max($dados_ticket_medio);
$pior_valor = min(array_filter($dados_ticket_medio, function($v) { return $v > 0; }));

if ($melhor_valor > 0) {
    $melhor_index = array_search($melhor_valor, $dados_ticket_medio);
    $melhor_periodo = $labels_ticket[$melhor_index];
}
if ($pior_valor > 0) {
    $pior_index = array_search($pior_valor, $dados_ticket_medio);
    $pior_periodo = $labels_ticket[$pior_index];
}

// Calcular crescimento anualizado se for comparação mensal
$crescimento_anual_text = '';
if ($ticket_mes > 0 && $variacao_ticket != 0) {
    $crescimento_anual_text = "Isso representa um crescimento " . ($variacao_ticket > 0 ? "de" : "negativo de") . " " . number_format(abs($variacao_ticket), 1, ',', '.') . "% ano a ano.";
}
?>

<!-- Comparação e Insights -->
<div class="comparison-section">
    <div class="comparison-grid">
        <!-- Card Comparação com Período Anterior -->
        <div class="comparison-card">
            <h3 class="comparison-title">📊 Comparação: <?php echo $periodo_nome; ?> vs <?php echo $periodo_anterior_nome; ?></h3>
            
            <div class="comparison-info-box">
                <div class="info-row">
                    <span class="info-label">📍 Período Atual:</span>
                    <span class="info-value current"><?php echo $periodo_nome; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php echo $ticket_mes > 0 ? '🔄 Mesmo período em:' : '🔄 Comparando com:'; ?></span>
                    <span class="info-value previous"><?php echo $periodo_anterior_nome; ?></span>
                </div>
                <?php if ($ticket_mes > 0): ?>
                <div class="info-row" style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 8px; margin-top: 5px;">
                    <span class="info-label" style="font-size: 11px;">💡 Comparação:</span>
                    <span class="info-value" style="font-size: 12px; font-weight: 500;">Análise ano a ano (YoY)</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="metrics-comparison">
                <!-- Ticket Médio -->
                <div class="metric-box">
                    <div class="metric-header">
                        <span class="metric-icon">💰</span>
                        <span class="metric-name">Ticket Médio</span>
                    </div>
                    <div class="metric-comparison-row">
                        <div class="metric-col current-col">
                            <div class="col-label">Atual</div>
                            <div class="col-value atual">R$ <?php echo number_format($ticket_medio_real, 2, ',', '.'); ?></div>
                        </div>
                        <div class="metric-arrow">
                            <?php if ($variacao_ticket >= 0): ?>
                                <div class="arrow-up">▲</div>
                            <?php else: ?>
                                <div class="arrow-down">▼</div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-col previous-col">
                            <div class="col-label">Anterior</div>
                            <div class="col-value anterior">R$ <?php echo number_format($ticket_anterior, 2, ',', '.'); ?></div>
                        </div>
                        <div class="metric-result <?php echo $variacao_ticket >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $variacao_ticket >= 0 ? '+' : ''; ?><?php echo number_format($variacao_ticket, 1, ',', '.'); ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Receita Total -->
                <div class="metric-box">
                    <div class="metric-header">
                        <span class="metric-icon">📈</span>
                        <span class="metric-name">Receita Total</span>
                    </div>
                    <div class="metric-comparison-row">
                        <div class="metric-col current-col">
                            <div class="col-label">Atual</div>
                            <div class="col-value atual">R$ <?php echo number_format($receita_total_periodo, 2, ',', '.'); ?></div>
                        </div>
                        <div class="metric-arrow">
                            <?php if ($variacao_receita >= 0): ?>
                                <div class="arrow-up">▲</div>
                            <?php else: ?>
                                <div class="arrow-down">▼</div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-col previous-col">
                            <div class="col-label">Anterior</div>
                            <div class="col-value anterior">R$ <?php echo number_format($receita_anterior, 2, ',', '.'); ?></div>
                        </div>
                        <div class="metric-result <?php echo $variacao_receita >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $variacao_receita >= 0 ? '+' : ''; ?><?php echo number_format($variacao_receita, 1, ',', '.'); ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Total Pagamentos -->
                <div class="metric-box">
                    <div class="metric-header">
                        <span class="metric-icon">📄</span>
                        <span class="metric-name">Total de Pagamentos</span>
                    </div>
                    <div class="metric-comparison-row">
                        <div class="metric-col current-col">
                            <div class="col-label">Atual</div>
                            <div class="col-value atual"><?php echo number_format($total_pagamentos_periodo, 0, ',', '.'); ?></div>
                        </div>
                        <div class="metric-arrow">
                            <?php if ($variacao_pagamentos >= 0): ?>
                                <div class="arrow-up">▲</div>
                            <?php else: ?>
                                <div class="arrow-down">▼</div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-col previous-col">
                            <div class="col-label">Anterior</div>
                            <div class="col-value anterior"><?php echo number_format($pagamentos_anterior, 0, ',', '.'); ?></div>
                        </div>
                        <div class="metric-result <?php echo $variacao_pagamentos >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $variacao_pagamentos >= 0 ? '+' : ''; ?><?php echo number_format($variacao_pagamentos, 1, ',', '.'); ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Resumo da Comparação -->
                <div class="comparison-summary">
                    <?php if ($variacao_ticket > 0 && $variacao_receita > 0): ?>
                        <div class="summary-icon success">✅</div>
                        <div class="summary-text">
                            <strong>Excelente!</strong> Tanto o ticket médio quanto a receita aumentaram em relação 
                            <?php echo $ticket_mes > 0 ? 'ao mesmo período do ano passado' : 'ao ano anterior'; ?>. 
                            Isso demonstra crescimento consistente do seu negócio!
                        </div>
                    <?php elseif ($variacao_ticket > 0): ?>
                        <div class="summary-icon info">💡</div>
                        <div class="summary-text">
                            <strong>Bom sinal!</strong> O ticket médio aumentou <?php echo number_format($variacao_ticket, 1, ',', '.'); ?>% 
                            em relação <?php echo $ticket_mes > 0 ? 'ao mesmo período do ano passado' : 'ao ano anterior'; ?>, 
                            indicando que cada pagamento tem maior valor.
                        </div>
                    <?php elseif ($variacao_receita > 0): ?>
                        <div class="summary-icon info">💡</div>
                        <div class="summary-text">
                            <strong>Crescimento!</strong> A receita aumentou <?php echo number_format($variacao_receita, 1, ',', '.'); ?>% 
                            em relação <?php echo $ticket_mes > 0 ? 'ao mesmo período do ano passado' : 'ao ano anterior'; ?>, 
                            mesmo com variação no ticket médio.
                        </div>
                    <?php else: ?>
                        <div class="summary-icon warning">⚠️</div>
                        <div class="summary-text">
                            <strong>Atenção!</strong> Houve redução em relação 
                            <?php echo $ticket_mes > 0 ? 'ao mesmo período do ano passado' : 'ao ano anterior'; ?>. 
                            <?php if ($ticket_mes > 0): ?>
                                Analise se há sazonalidade ou se são necessárias ações de recuperação.
                            <?php else: ?>
                                Avalie estratégias de recuperação e crescimento para o próximo ano.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Card Insights Automáticos -->
        <div class="insights-card">
            <h3 class="comparison-title">💡 Insights Automáticos</h3>
            
            <div class="insight-auto">
                <div class="insight-icon-auto"><?php echo $tendencia_icon; ?></div>
                <div class="insight-text">
                    <?php echo $tendencia; ?>
                    <?php if ($crescimento_anual_text): ?>
                        <br><small style="color: #6B7280; font-weight: 500;"><?php echo $crescimento_anual_text; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($melhor_periodo): ?>
            <div class="insight-auto">
                <div class="insight-icon-auto">🏆</div>
                <div class="insight-text">
                    <strong>Melhor Período:</strong> <?php echo $ticket_mes > 0 ? "Dia $melhor_periodo" : $melhor_periodo; ?>
                    <span class="highlight-value">R$ <?php echo number_format($melhor_valor, 2, ',', '.'); ?></span>
                    <br><small style="color: #6B7280;">Este foi o dia/mês com maior ticket médio do período analisado.</small>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($pior_periodo && $pior_valor < $ticket_medio_real): ?>
            <div class="insight-auto">
                <div class="insight-icon-auto">⚠️</div>
                <div class="insight-text">
                    <strong>Atenção:</strong> <?php echo $ticket_mes > 0 ? "Dia $pior_periodo" : $pior_periodo; ?> teve o menor ticket
                    <span class="highlight-value-low">R$ <?php echo number_format($pior_valor, 2, ',', '.'); ?></span>
                    <br><small style="color: #6B7280;">Analise o que pode ter causado essa redução neste período.</small>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            // Insights adicionais baseados na comparação anual
            if ($ticket_mes > 0) {
                if ($variacao_ticket > 10) {
                    ?>
                    <div class="insight-auto" style="border-left-color: #10B981;">
                        <div class="insight-icon-auto">🚀</div>
                        <div class="insight-text">
                            <strong>Crescimento Forte!</strong> Seu ticket médio está 
                            <span class="highlight-value"><?php echo number_format($variacao_ticket, 1, ',', '.'); ?>% maior</span> 
                            que no mesmo período do ano passado. Ótimo trabalho!
                        </div>
                    </div>
                    <?php
                } elseif ($variacao_ticket < -10) {
                    ?>
                    <div class="insight-auto" style="border-left-color: #EF4444;">
                        <div class="insight-icon-auto">📉</div>
                        <div class="insight-text">
                            <strong>Requer Atenção:</strong> O ticket médio está 
                            <span class="highlight-value-low"><?php echo number_format(abs($variacao_ticket), 1, ',', '.'); ?>% menor</span> 
                            que no mesmo período do ano passado. Considere estratégias de recuperação.
                        </div>
                    </div>
                    <?php
                }
                
                // Comparação de sazonalidade
                if ($variacao_receita > 0 && $variacao_ticket < 0) {
                    ?>
                    <div class="insight-auto" style="border-left-color: #F59E0B;">
                        <div class="insight-icon-auto">💡</div>
                        <div class="insight-text">
                            <strong>Insight de Sazonalidade:</strong> A receita aumentou mas o ticket médio caiu. 
                            Isso indica mais volume de pagamentos com menor valor individual. 
                            Considere estratégias de upsell.
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>

<style>
.comparison-section {
    margin: 25px 0;
}

.comparison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
}

.comparison-card,
.insights-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    border: 2px solid #E5E7EB;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.comparison-title {
    font-size: 18px;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E5E7EB;
}

.comparison-info-box {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.info-label {
    color: rgba(255,255,255,0.9);
    font-size: 13px;
    font-weight: 600;
}

.info-value {
    font-size: 15px;
    font-weight: 700;
    color: white;
}

.info-value.current {
    background: rgba(16, 185, 129, 0.3);
    padding: 4px 12px;
    border-radius: 6px;
}

.info-value.previous {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 6px;
}

.comparison-period {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #F3F4F6;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.period-label {
    font-weight: 600;
    color: #6B7280;
    font-size: 13px;
}

.period-value {
    font-weight: 700;
    color: #4F46E5;
    font-size: 15px;
}

.metrics-comparison {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.metric-box {
    background: #F9FAFB;
    border-radius: 10px;
    padding: 15px;
    border: 2px solid #E5E7EB;
}

.metric-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #E5E7EB;
}

.metric-icon {
    font-size: 20px;
}

.metric-name {
    font-size: 14px;
    font-weight: 700;
    color: #1F2937;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-comparison-row {
    display: grid;
    grid-template-columns: 1fr auto 1fr auto;
    gap: 15px;
    align-items: center;
}

.metric-col {
    text-align: center;
}

.col-label {
    font-size: 11px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    margin-bottom: 5px;
    letter-spacing: 0.5px;
}

.col-value {
    font-size: 18px;
    font-weight: 700;
}

.current-col .col-value.atual {
    color: #10B981;
}

.previous-col .col-value.anterior {
    color: #6B7280;
}

.metric-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
}

.arrow-up,
.arrow-down {
    font-size: 24px;
    font-weight: bold;
}

.arrow-up {
    color: #10B981;
}

.arrow-down {
    color: #EF4444;
}

.metric-result {
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 700;
    text-align: center;
    min-width: 80px;
}

.metric-result.positive {
    background: #D1FAE5;
    color: #065F46;
}

.metric-result.negative {
    background: #FEE2E2;
    color: #991B1B;
}

.comparison-summary {
    display: flex;
    align-items: center;
    gap: 15px;
    background: white;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #4F46E5;
    margin-top: 10px;
}

.summary-icon {
    font-size: 28px;
    flex-shrink: 0;
}

.summary-icon.success {
    color: #10B981;
}

.summary-icon.info {
    color: #3B82F6;
}

.summary-icon.warning {
    color: #F59E0B;
}

.summary-text {
    font-size: 14px;
    line-height: 1.5;
    color: #374151;
}

.summary-text strong {
    color: #1F2937;
    font-weight: 700;
}

.metric-item {
    padding: 15px;
    background: #F9FAFB;
    border-radius: 8px;
    border-left: 4px solid #4F46E5;
}

.metric-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.metric-values {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.value-atual {
    font-size: 18px;
    font-weight: 700;
    color: #1F2937;
}

.vs-text {
    font-size: 12px;
    color: #9CA3AF;
    font-weight: 600;
}

.value-anterior {
    font-size: 14px;
    color: #6B7280;
}

.variation {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
}

.variation.positive {
    background: #D1FAE5;
    color: #065F46;
}

.variation.negative {
    background: #FEE2E2;
    color: #991B1B;
}

.insight-auto {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    background: #F9FAFB;
    border-radius: 8px;
    margin-bottom: 12px;
    border-left: 4px solid #8B5CF6;
}

.insight-icon-auto {
    font-size: 24px;
    flex-shrink: 0;
}

.insight-text {
    flex: 1;
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
}

.highlight-value {
    display: inline-block;
    background: #DDD6FE;
    color: #5B21B6;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 700;
    margin-left: 5px;
}

.highlight-value-low {
    display: inline-block;
    background: #FEE2E2;
    color: #991B1B;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 700;
    margin-left: 5px;
}

@media (max-width: 768px) {
    .comparison-grid {
        grid-template-columns: 1fr;
    }
    
    .metric-comparison-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .metric-arrow {
        transform: rotate(90deg);
    }
    
    .metric-result {
        justify-self: center;
    }
    
    .comparison-summary {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<!-- Tabela Detalhada -->
<div class="monthly-table">
    <div class="chart-header">
        <div class="chart-title">📋 Detalhamento do Ticket Médio - <?php echo $periodo_nome; ?></div>
    </div>
    <table>
        <thead>
            <tr>
                <th><?php echo $ticket_mes > 0 ? 'Dia' : 'Mês'; ?></th>
                <th>Receita Total</th>
                <th>Total Pagamentos</th>
                <th>Ticket Médio</th>
                <th>% da Média</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query_detalhado = $ticket_mes > 0
                ? "SELECT 
                    DAY(data) as numero_periodo,
                    SUM(entrada) as receita,
                    COUNT(CASE WHEN entrada > 0 THEN 1 END) as pagamentos
                   FROM sis_caixa
                   WHERE YEAR(data) = $ticket_ano 
                   AND MONTH(data) = $ticket_mes
                   AND entrada > 0
                   GROUP BY DAY(data)
                   ORDER BY DAY(data) ASC"
                : "SELECT 
                    MONTH(data) as numero_periodo,
                    SUM(entrada) as receita,
                    COUNT(CASE WHEN entrada > 0 THEN 1 END) as pagamentos
                   FROM sis_caixa
                   WHERE YEAR(data) = $ticket_ano
                   AND entrada > 0
                   GROUP BY MONTH(data)
                   ORDER BY MONTH(data) ASC";
            
            $result_detalhado = mysqli_query($link, $query_detalhado);
            
            $meses_completos = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            
            while ($row = mysqli_fetch_assoc($result_detalhado)) {
                $numero = $row['numero_periodo'];
                $receita = floatval($row['receita']);
                $pagamentos = intval($row['pagamentos']);
                $ticket = $pagamentos > 0 ? $receita / $pagamentos : 0;
                $percentual = $ticket_medio_real > 0 ? ($ticket / $ticket_medio_real) * 100 : 0;
                
                $label = $ticket_mes > 0 ? "Dia $numero" : $meses_completos[$numero];
                
                $percent_class = $percentual >= 100 ? 'color: #10B981;' : 'color: #EF4444;';
                
                echo "<tr>";
                echo "<td style='font-weight: 600;'>$label</td>";
                echo "<td style='color: #10B981; font-weight: 600;'>R$ " . number_format($receita, 2, ',', '.') . "</td>";
                echo "<td style='font-weight: 600;'>$pagamentos</td>";
                echo "<td style='color: #3B82F6; font-weight: 700; font-size: 15px;'>R$ " . number_format($ticket, 2, ',', '.') . "</td>";
                echo "<td style='$percent_class font-weight: 600;'>" . number_format($percentual, 1, ',', '.') . "%</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<style>
/* Estilos específicos para Ticket Médio */
.stat-card.ticket-card::before { background: #8B5CF6; }
.stat-card.receita-card::before { background: #10B981; }
.stat-card.clientes-card::before { background: #3B82F6; }
.stat-card.variacao-card::before { background: #F59E0B; }

.stat-card.ticket-card .stat-value { color: #8B5CF6; }
.stat-card.receita-card .stat-value { color: #10B981; }
.stat-card.clientes-card .stat-value { color: #3B82F6; }
.stat-card.variacao-card .stat-value { color: #F59E0B; font-size: 18px; }

.btn-secondary {
    background: white;
    color: #1F2937;
    border: 2px solid #4F46E5;
}

.btn-secondary:hover {
    background: #4F46E5;
    color: white;
}

@media (max-width: 768px) {
    .formula {
        flex-direction: column;
        gap: 10px;
    }
    
    .insights-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Dados do gráfico
const labelsTicket = <?php echo $json_labels_ticket; ?>;
const dadosTicket = <?php echo $json_dados_ticket; ?>;

let chartTicket = null;

function initTicketChart() {
    const ctx = document.getElementById('chartTicketMedio');
    if (!ctx) return;
    
    if (chartTicket) {
        chartTicket.destroy();
    }
    
    // Calcular média para linha de referência
    const media = dadosTicket.reduce((a, b) => a + b, 0) / dadosTicket.filter(v => v > 0).length;
    
    chartTicket = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labelsTicket,
            datasets: [
                {
                    label: 'Ticket Médio (R$)',
                    data: dadosTicket,
                    backgroundColor: 'rgba(139, 92, 246, 0.2)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(139, 92, 246, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                },
                {
                    label: 'Média do Período',
                    data: Array(labelsTicket.length).fill(media),
                    borderColor: 'rgba(239, 68, 68, 0.8)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0,
                    pointHoverRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        padding: 15,
                        font: {
                            size: 13,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                const valor = context.parsed.y;
                                const diff = ((valor - media) / media * 100).toFixed(1);
                                const sinal = diff >= 0 ? '+' : '';
                                return 'Ticket: R$ ' + valor.toFixed(2).replace('.', ',') + ' (' + sinal + diff + '% da média)';
                            } else {
                                return 'Média: R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(2).replace('.', ',');
                        }
                    }
                }
            }
        }
    });
}

function atualizarTicket() {
    // Mostrar loading
    const loading = document.getElementById('loading-ticket');
    if (loading) loading.style.display = 'inline-block';
    
    const ano = document.getElementById('ticket_ano').value;
    const mes = document.getElementById('ticket_mes').value;
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('ticket_ano', ano);
    currentUrl.searchParams.set('ticket_mes', mes);
    currentUrl.searchParams.set('tab', 'ticket');
    window.location.href = currentUrl.toString();
}

function exportarCSV() {
    const labels = <?php echo $json_labels_ticket; ?>;
    const dados = <?php echo $json_dados_ticket; ?>;
    const periodo = '<?php echo $periodo_nome; ?>';
    
    // Cabeçalho do CSV
    let csv = 'Ticket Medio - ' + periodo + '\n\n';
    csv += 'Periodo;Ticket Medio (R$)\n';
    
    // Dados
    for (let i = 0; i < labels.length; i++) {
        if (dados[i] > 0) {
            csv += labels[i] + ';' + dados[i].toFixed(2).replace('.', ',') + '\n';
        }
    }
    
    // Resumo
    csv += '\nResumo\n';
    csv += 'Ticket Medio Periodo;<?php echo number_format($ticket_medio_real, 2, ',', '.'); ?>\n';
    csv += 'Receita Total;<?php echo number_format($receita_total_periodo, 2, ',', '.'); ?>\n';
    csv += 'Total Pagamentos;<?php echo $total_pagamentos_periodo; ?>\n';
    csv += 'Ticket Minimo;<?php echo number_format($ticket_minimo, 2, ',', '.'); ?>\n';
    csv += 'Ticket Maximo;<?php echo number_format($ticket_maximo, 2, ',', '.'); ?>\n';
    
    // Criar blob e download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'ticket_medio_' + periodo.replace(/ /g, '_').toLowerCase() + '.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Auto-inicializar gráfico quando a aba estiver ativa
document.addEventListener('DOMContentLoaded', function() {
    const ticketTab = document.getElementById('content-ticket');
    if (ticketTab && ticketTab.classList.contains('active')) {
        setTimeout(initTicketChart, 100);
    }
});
</script>