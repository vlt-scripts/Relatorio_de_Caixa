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
// ARQUIVO: evolucao_content.php
// Este arquivo contém a análise de evolução financeira ao longo dos anos

// Buscar todos os anos disponíveis no banco
$query_anos = "
    SELECT DISTINCT YEAR(data) as ano 
    FROM sis_caixa 
    WHERE YEAR(data) >= 2015
    ORDER BY ano ASC
";

$result_anos = mysqli_query($link, $query_anos);
$anos_disponiveis = [];

while ($row = mysqli_fetch_assoc($result_anos)) {
    $anos_disponiveis[] = intval($row['ano']);
}

// Arrays para armazenar dados
$dados_evolucao = [];
$anos_labels = [];
$receitas_data = [];
$despesas_data = [];
$saldo_data = [];
$ticket_medio_data = [];
$pagamentos_data = [];

// Buscar dados de cada ano
foreach ($anos_disponiveis as $ano) {
    $query_ano = "
        SELECT 
            YEAR(data) as ano,
            SUM(entrada) as receita_total,
            SUM(saida) as despesa_total,
            COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_pagamentos
        FROM sis_caixa
        WHERE YEAR(data) = $ano
        GROUP BY YEAR(data)
    ";
    
    $result_ano = mysqli_query($link, $query_ano);
    $dados_ano = mysqli_fetch_assoc($result_ano);
    
    if ($dados_ano) {
        $receita = floatval($dados_ano['receita_total']);
        $despesa = floatval($dados_ano['despesa_total']);
        $saldo = $receita - $despesa;
        $pagamentos = intval($dados_ano['total_pagamentos']);
        $ticket_medio = $pagamentos > 0 ? $receita / $pagamentos : 0;
        
        $dados_evolucao[$ano] = [
            'receita' => $receita,
            'despesa' => $despesa,
            'saldo' => $saldo,
            'pagamentos' => $pagamentos,
            'ticket_medio' => $ticket_medio
        ];
        
        $anos_labels[] = $ano;
        $receitas_data[] = round($receita, 2);
        $despesas_data[] = round($despesa, 2);
        $saldo_data[] = round($saldo, 2);
        $ticket_medio_data[] = round($ticket_medio, 2);
        $pagamentos_data[] = $pagamentos;
    }
}

// Calcular crescimentos e estatísticas
$total_anos = count($anos_labels);
$crescimentos = [];

for ($i = 1; $i < $total_anos; $i++) {
    $ano_atual = $anos_labels[$i];
    $ano_anterior = $anos_labels[$i - 1];
    
    $receita_atual = $dados_evolucao[$ano_atual]['receita'];
    $receita_anterior = $dados_evolucao[$ano_anterior]['receita'];
    
    $crescimento_receita = $receita_anterior > 0 ? (($receita_atual - $receita_anterior) / $receita_anterior) * 100 : 0;
    
    $crescimentos[$ano_atual] = $crescimento_receita;
}

// Calcular CAGR (Taxa de Crescimento Anual Composta)
$cagr = 0;
if ($total_anos > 1) {
    $receita_inicial = $dados_evolucao[$anos_labels[0]]['receita'];
    $receita_final = $dados_evolucao[$anos_labels[$total_anos - 1]]['receita'];
    $anos_periodo = $total_anos - 1;
    
    if ($receita_inicial > 0 && $receita_final > 0) {
        $cagr = (pow($receita_final / $receita_inicial, 1 / $anos_periodo) - 1) * 100;
    }
}

// Melhor e pior ano
$melhor_ano = '';
$melhor_receita = 0;
$pior_ano = '';
$pior_receita = PHP_FLOAT_MAX;

foreach ($dados_evolucao as $ano => $dados) {
    if ($dados['receita'] > $melhor_receita) {
        $melhor_receita = $dados['receita'];
        $melhor_ano = $ano;
    }
    if ($dados['receita'] < $pior_receita && $dados['receita'] > 0) {
        $pior_receita = $dados['receita'];
        $pior_ano = $ano;
    }
}

// Projeção para próximo ano (baseada na média dos últimos 3 anos)
$ultimos_anos = array_slice($crescimentos, -3, 3, true);
$crescimento_medio = count($ultimos_anos) > 0 ? array_sum($ultimos_anos) / count($ultimos_anos) : 0;
$proximo_ano = $anos_labels[$total_anos - 1] + 1;
$receita_atual_final = $dados_evolucao[$anos_labels[$total_anos - 1]]['receita'];
$projecao_proximo_ano = $receita_atual_final * (1 + ($crescimento_medio / 100));

// Converter para JSON
$json_anos = json_encode($anos_labels);
$json_receitas = json_encode($receitas_data);
$json_despesas = json_encode($despesas_data);
$json_saldo = json_encode($saldo_data);
$json_ticket_medio = json_encode($ticket_medio_data);
$json_pagamentos = json_encode($pagamentos_data);
?>

<!-- Cards de Resumo Geral -->
<div class="evolucao-header">
    <h2 class="evolucao-title">📈 Evolução Financeira: <?php echo $anos_labels[0]; ?> - <?php echo $anos_labels[$total_anos - 1]; ?></h2>
    <p class="evolucao-subtitle">Análise histórica de <?php echo $total_anos; ?> anos de operação</p>
</div>

<div class="stats-grid">
    <div class="stat-card cagr-card">
        <div class="stat-header">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-label">CAGR (Crescimento Anual)</div>
                <div class="stat-value" style="color: <?php echo $cagr >= 0 ? '#10B981' : '#EF4444'; ?>;">
                    <?php echo number_format($cagr, 2, ',', '.'); ?>%
                </div>
                <div class="stat-trend">Taxa de crescimento composta</div>
            </div>
        </div>
    </div>

    <div class="stat-card total-card">
        <div class="stat-header">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-label">Receita Total Acumulada</div>
                <div class="stat-value">R$ <?php echo number_format(array_sum($receitas_data), 2, ',', '.'); ?></div>
                <div class="stat-trend">Soma de todos os anos</div>
            </div>
        </div>
    </div>

    <div class="stat-card melhor-card">
        <div class="stat-header">
            <div class="stat-icon">🏆</div>
            <div class="stat-info">
                <div class="stat-label">Melhor Ano</div>
                <div class="stat-value"><?php echo $melhor_ano; ?></div>
                <div class="stat-trend">R$ <?php echo number_format($melhor_receita, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <div class="stat-card projecao-card">
        <div class="stat-header">
            <div class="stat-icon">🔮</div>
            <div class="stat-info">
                <div class="stat-label">Projeção <?php echo $proximo_ano; ?></div>
                <div class="stat-value">R$ <?php echo number_format($projecao_proximo_ano, 2, ',', '.'); ?></div>
                <div class="stat-trend">Baseado na tendência recente</div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos de Evolução -->
<div class="charts-grid">
    <div class="chart-container" style="grid-column: 1 / -1;">
        <div class="chart-header">
            <div class="chart-title">📈 Evolução de Receitas, Despesas e Saldo</div>
            <div class="chart-subtitle">Análise completa ano a ano</div>
        </div>
        <div class="chart-wrapper" style="height: 450px;">
            <canvas id="chartEvolucaoGeral"></canvas>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title">💰 Evolução do Ticket Médio</div>
            <div class="chart-subtitle">Valor médio por pagamento ao longo dos anos</div>
        </div>
        <div class="chart-wrapper" style="height: 350px;">
            <canvas id="chartTicketEvolucao"></canvas>
        </div>
    </div>

    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title">📄 Total de Pagamentos</div>
            <div class="chart-subtitle">Volume de pagamentos recebidos</div>
        </div>
        <div class="chart-wrapper" style="height: 350px;">
            <canvas id="chartPagamentosEvolucao"></canvas>
        </div>
    </div>
</div>

<!-- Tabela de Evolução Detalhada -->
<div class="monthly-table">
    <div class="chart-header">
        <div class="chart-title">📋 Análise Detalhada por Ano</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Ano</th>
                <th>Receita</th>
                <th>Despesas</th>
                <th>Saldo</th>
                <th>Crescimento</th>
                <th>Ticket Médio</th>
                <th>Pagamentos</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($anos_labels as $index => $ano) {
                $dados = $dados_evolucao[$ano];
                $crescimento = isset($crescimentos[$ano]) ? $crescimentos[$ano] : null;
                
                $crescimento_class = '';
                $crescimento_texto = '--';
                if ($crescimento !== null) {
                    $crescimento_class = $crescimento >= 0 ? 'positive' : 'negative';
                    $crescimento_texto = ($crescimento >= 0 ? '+' : '') . number_format($crescimento, 1, ',', '.') . '%';
                }
                
                $destaque = $ano == $melhor_ano ? 'style="background: #F0FDF4;"' : '';
                
                echo "<tr $destaque>";
                echo "<td style='font-weight: 700; font-size: 15px;'>" . $ano . ($ano == $melhor_ano ? " 🏆" : "") . "</td>";
                echo "<td style='color: #10B981; font-weight: 700;'>R$ " . number_format($dados['receita'], 2, ',', '.') . "</td>";
                echo "<td style='color: #EF4444; font-weight: 600;'>R$ " . number_format($dados['despesa'], 2, ',', '.') . "</td>";
                echo "<td style='color: #3B82F6; font-weight: 700; font-size: 15px;'>R$ " . number_format($dados['saldo'], 2, ',', '.') . "</td>";
                echo "<td><span class='variation $crescimento_class' style='font-size: 14px;'>$crescimento_texto</span></td>";
                echo "<td style='font-weight: 600;'>R$ " . number_format($dados['ticket_medio'], 2, ',', '.') . "</td>";
                echo "<td style='font-weight: 600;'>" . number_format($dados['pagamentos'], 0, ',', '.') . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
        <tfoot style="background: #F3F4F6; font-weight: 700;">
            <tr>
                <td>TOTAL</td>
                <td style="color: #10B981;">R$ <?php echo number_format(array_sum($receitas_data), 2, ',', '.'); ?></td>
                <td style="color: #EF4444;">R$ <?php echo number_format(array_sum($despesas_data), 2, ',', '.'); ?></td>
                <td style="color: #3B82F6;">R$ <?php echo number_format(array_sum($saldo_data), 2, ',', '.'); ?></td>
                <td colspan="3" style="text-align: center;">CAGR: <?php echo number_format($cagr, 2, ',', '.'); ?>%</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Insights de Crescimento -->
<div class="insights-evolution">
    <h3 class="insights-title">💡 Análise de Crescimento</h3>
    
    <div class="insights-grid-evolution">
        <?php if ($cagr > 15): ?>
        <div class="insight-evolution success">
            <div class="insight-evo-icon">🚀</div>
            <div class="insight-evo-content">
                <strong>Crescimento Excepcional!</strong>
                <p>Seu negócio apresenta uma taxa de crescimento anual composta (CAGR) de 
                <span class="highlight"><?php echo number_format($cagr, 1, ',', '.'); ?>%</span>. 
                Isso indica um crescimento muito acima da média do mercado!</p>
            </div>
        </div>
        <?php elseif ($cagr > 5): ?>
        <div class="insight-evolution good">
            <div class="insight-evo-icon">📈</div>
            <div class="insight-evo-content">
                <strong>Crescimento Saudável</strong>
                <p>Com um CAGR de <span class="highlight"><?php echo number_format($cagr, 1, ',', '.'); ?>%</span>, 
                seu negócio apresenta crescimento consistente ano após ano.</p>
            </div>
        </div>
        <?php elseif ($cagr > 0): ?>
        <div class="insight-evolution warning">
            <div class="insight-evo-icon">⚠️</div>
            <div class="insight-evo-content">
                <strong>Crescimento Moderado</strong>
                <p>O CAGR de <span class="highlight"><?php echo number_format($cagr, 1, ',', '.'); ?>%</span> 
                indica crescimento lento. Considere estratégias para acelerar a expansão.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="insight-evolution danger">
            <div class="insight-evo-icon">📉</div>
            <div class="insight-evo-content">
                <strong>Atenção Necessária</strong>
                <p>O negócio apresenta retração. É fundamental revisar estratégias e implementar 
                ações corretivas imediatamente.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        $anos_positivos = 0;
        foreach ($crescimentos as $cresc) {
        if ($cresc > 0) $anos_positivos++;
        }

        $total_crescimentos = count($crescimentos);
        $consistencia = ($total_crescimentos > 0) ? ($anos_positivos / $total_crescimentos) * 100 : 0;
        ?>
        
        <div class="insight-evolution <?php echo $consistencia >= 70 ? 'good' : 'warning'; ?>">
            <div class="insight-evo-icon">🎯</div>
            <div class="insight-evo-content">
                <strong>Consistência de Crescimento</strong>
                <p>Em <span class="highlight"><?php echo $anos_positivos; ?> de <?php echo count($crescimentos); ?> anos</span> 
                houve crescimento positivo (<span class="highlight"><?php echo number_format($consistencia, 0); ?>%</span>). 
                <?php echo $consistencia >= 70 ? 'Excelente consistência!' : 'Busque maior estabilidade.'; ?></p>
            </div>
        </div>
        
        <div class="insight-evolution info">
            <div class="insight-evo-icon">🔮</div>
            <div class="insight-evo-content">
                <strong>Projeção para <?php echo $proximo_ano; ?></strong>
                <p>Baseado na tendência dos últimos anos, a expectativa é alcançar 
                <span class="highlight">R$ <?php echo number_format($projecao_proximo_ano, 2, ',', '.'); ?></span> 
                em receitas (crescimento de <span class="highlight"><?php echo number_format($crescimento_medio, 1, ',', '.'); ?>%</span>).</p>
            </div>
        </div>
    </div>
</div>

<style>
.evolucao-header {
    margin-bottom: 25px;
    padding: 20px;
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    border-radius: 10px;
    color: white;
}

.evolucao-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 5px;
}

.evolucao-subtitle {
    font-size: 14px;
    opacity: 0.9;
}

.stat-card.cagr-card::before { background: #8B5CF6; }
.stat-card.total-card::before { background: #10B981; }
.stat-card.melhor-card::before { background: #F59E0B; }
.stat-card.projecao-card::before { background: #3B82F6; }

.insights-evolution {
    margin-top: 30px;
    padding: 25px;
    background: white;
    border-radius: 10px;
    border: 2px solid #E5E7EB;
}

.insights-title {
    font-size: 20px;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E5E7EB;
}

.insights-grid-evolution {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.insight-evolution {
    display: flex;
    gap: 15px;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid;
}

.insight-evolution.success {
    background: #F0FDF4;
    border-left-color: #10B981;
}

.insight-evolution.good {
    background: #EFF6FF;
    border-left-color: #3B82F6;
}

.insight-evolution.warning {
    background: #FFFBEB;
    border-left-color: #F59E0B;
}

.insight-evolution.danger {
    background: #FEF2F2;
    border-left-color: #EF4444;
}

.insight-evolution.info {
    background: #F5F3FF;
    border-left-color: #8B5CF6;
}

.insight-evo-icon {
    font-size: 32px;
    flex-shrink: 0;
}

.insight-evo-content {
    flex: 1;
}

.insight-evo-content strong {
    display: block;
    font-size: 16px;
    color: #1F2937;
    margin-bottom: 8px;
}

.insight-evo-content p {
    font-size: 14px;
    color: #374151;
    line-height: 1.6;
    margin: 0;
}

.insight-evo-content .highlight {
    font-weight: 700;
    color: #4F46E5;
}

@media (max-width: 768px) {
    .insights-grid-evolution {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Dados dos gráficos
const anosLabels = <?php echo $json_anos; ?>;
const receitasData = <?php echo $json_receitas; ?>;
const despesasData = <?php echo $json_despesas; ?>;
const saldoData = <?php echo $json_saldo; ?>;
const ticketMedioData = <?php echo $json_ticket_medio; ?>;
const pagamentosData = <?php echo $json_pagamentos; ?>;

let chartsEvolucao = {};

function initEvolucaoCharts() {
    // Gráfico Principal - Evolução Geral
    const ctxGeral = document.getElementById('chartEvolucaoGeral');
    if (ctxGeral) {
        chartsEvolucao.geral = new Chart(ctxGeral.getContext('2d'), {
            type: 'line',
            data: {
                labels: anosLabels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: receitasData,
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Despesas',
                        data: despesasData,
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Saldo',
                        data: saldoData,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
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
                            padding: 20,
                            font: { size: 13, weight: 'bold' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + 
                                       context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico Ticket Médio
    const ctxTicket = document.getElementById('chartTicketEvolucao');
    if (ctxTicket) {
        chartsEvolucao.ticket = new Chart(ctxTicket.getContext('2d'), {
            type: 'bar',
            data: {
                labels: anosLabels,
                datasets: [{
                    label: 'Ticket Médio',
                    data: ticketMedioData,
                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
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
    
    // Gráfico Pagamentos
    const ctxPagamentos = document.getElementById('chartPagamentosEvolucao');
    if (ctxPagamentos) {
        chartsEvolucao.pagamentos = new Chart(ctxPagamentos.getContext('2d'), {
            type: 'bar',
            data: {
                labels: anosLabels,
                datasets: [{
                    label: 'Total de Pagamentos',
                    data: pagamentosData,
                    backgroundColor: 'rgba(245, 158, 11, 0.7)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
}

// Auto-inicializar
document.addEventListener('DOMContentLoaded', function() {
    const evolucaoTab = document.getElementById('content-evolucao');
    if (evolucaoTab && evolucaoTab.classList.contains('active')) {
        setTimeout(initEvolucaoCharts, 100);
    }
});
</script>