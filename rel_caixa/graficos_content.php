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
// ARQUIVO: graficos_content.php
// Este arquivo contém todo o conteúdo da aba de gráficos

// Filtros
?>

<div class="filter-section">
    <div class="filter-group">
        <label for="ano">📅 Ano</label>
        <select id="ano" name="ano" onchange="atualizarGraficos()">
            <?php
            $ano_atual = date('Y');
            $ano_selecionado = isset($_GET['ano']) ? intval($_GET['ano']) : $ano_atual;
            for ($i = $ano_atual; $i >= $ano_atual - 10; $i--) {
                $selected = ($i == $ano_selecionado) ? 'selected' : '';
                echo "<option value='$i' $selected>$i</option>";
            }
            ?>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="mes">📆 Mês</label>
        <select id="mes" name="mes" onchange="atualizarGraficos()">
            <?php
            $mes_selecionado = isset($_GET['mes']) ? intval($_GET['mes']) : 0;
            $meses_lista = [
                0 => 'Todos os Meses',
                1 => 'Janeiro',
                2 => 'Fevereiro',
                3 => 'Março',
                4 => 'Abril',
                5 => 'Maio',
                6 => 'Junho',
                7 => 'Julho',
                8 => 'Agosto',
                9 => 'Setembro',
                10 => 'Outubro',
                11 => 'Novembro',
                12 => 'Dezembro'
            ];
            foreach ($meses_lista as $num => $nome) {
                $selected = ($num == $mes_selecionado) ? 'selected' : '';
                echo "<option value='$num' $selected>$nome</option>";
            }
            ?>
        </select>
    </div>
    
    <!--<button class="btn btn-primary" onclick="atualizarGraficos()">🔄 Atualizar</button>-->
</div>

<?php
// Buscar dados do banco para gráficos
$ano_grafico = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$mes_grafico = isset($_GET['mes']) ? intval($_GET['mes']) : 0;

// Preparar arrays para os gráficos
$labels = [];
$dados_entradas = [];
$dados_saidas = [];
$dados_saldo = [];
$dados_boletos = [];

$total_periodo_entrada = 0;
$total_periodo_saida = 0;
$total_periodo_boletos = 0;

if ($mes_grafico > 0) {
    // FILTRO POR MÊS ESPECÍFICO - Mostra dados DIÁRIOS
    $dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mes_grafico, $ano_grafico);
    
    $query_diaria = "
        SELECT 
            DAY(data) as dia,
            SUM(entrada) as total_entrada,
            SUM(saida) as total_saida,
            COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_boletos
        FROM sis_caixa
        WHERE YEAR(data) = $ano_grafico AND MONTH(data) = $mes_grafico
        GROUP BY DAY(data)
        ORDER BY dia ASC
    ";
    
    $result_diaria = mysqli_query($link, $query_diaria);
    
    // Inicializar arrays com todos os dias do mês
    for ($i = 1; $i <= $dias_no_mes; $i++) {
        $labels[] = $i;
        $dados_entradas[$i] = 0;
        $dados_saidas[$i] = 0;
        $dados_saldo[$i] = 0;
        $dados_boletos[$i] = 0;
    }
    
    // Preencher com dados do banco
    while ($row = mysqli_fetch_assoc($result_diaria)) {
        $dia = $row['dia'];
        $entrada = floatval($row['total_entrada']);
        $saida = floatval($row['total_saida']);
        
        $dados_entradas[$dia] = $entrada;
        $dados_saidas[$dia] = $saida;
        $dados_saldo[$dia] = $entrada - $saida;
        $dados_boletos[$dia] = intval($row['total_boletos']);
        
        $total_periodo_entrada += $entrada;
        $total_periodo_saida += $saida;
        $total_periodo_boletos += intval($row['total_boletos']);
    }
    
    // Reorganizar arrays para manter apenas os valores
    $dados_entradas = array_values($dados_entradas);
    $dados_saidas = array_values($dados_saidas);
    $dados_saldo = array_values($dados_saldo);
    $dados_boletos = array_values($dados_boletos);
    
} else {
    // FILTRO POR ANO - Mostra dados MENSAIS
    $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $labels = $meses;
    $dados_entradas = array_fill(0, 12, 0);
    $dados_saidas = array_fill(0, 12, 0);
    $dados_saldo = array_fill(0, 12, 0);
    $dados_boletos = array_fill(0, 12, 0);
    
    $query_mensal = "
        SELECT 
            MONTH(data) as mes,
            SUM(entrada) as total_entrada,
            SUM(saida) as total_saida,
            COUNT(CASE WHEN entrada > 0 THEN 1 END) as total_boletos
        FROM sis_caixa
        WHERE YEAR(data) = $ano_grafico
        GROUP BY MONTH(data)
        ORDER BY mes ASC
    ";
    
    $result_mensal = mysqli_query($link, $query_mensal);
    
    while ($row = mysqli_fetch_assoc($result_mensal)) {
        $mes_index = $row['mes'] - 1;
        $entrada = floatval($row['total_entrada']);
        $saida = floatval($row['total_saida']);
        
        $dados_entradas[$mes_index] = $entrada;
        $dados_saidas[$mes_index] = $saida;
        $dados_saldo[$mes_index] = $entrada - $saida;
        $dados_boletos[$mes_index] = intval($row['total_boletos']);
        
        $total_periodo_entrada += $entrada;
        $total_periodo_saida += $saida;
        $total_periodo_boletos += intval($row['total_boletos']);
    }
}

$saldo_periodo = $total_periodo_entrada - $total_periodo_saida;

// Converter para JSON para usar no JavaScript
$json_labels = json_encode($labels);
$json_entradas = json_encode($dados_entradas);
$json_saidas = json_encode($dados_saidas);
$json_saldo = json_encode($dados_saldo);
$json_boletos = json_encode($dados_boletos);

// Nome do período para exibição
$meses_nomes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$periodo_nome = $mes_grafico > 0 ? $meses_nomes[$mes_grafico] . ' de ' . $ano_grafico : $ano_grafico;
?>

<!-- Cards de Estatísticas -->
<div class="stats-grid">
    <div class="stat-card entradas">
        <div class="stat-header">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-label">Total Entradas - <?php echo $periodo_nome; ?></div>
                <div class="stat-value">R$ <?php echo number_format($total_periodo_entrada, 2, ',', '.'); ?></div>
                <div class="stat-trend">Receita total do período</div>
            </div>
        </div>
    </div>

    <div class="stat-card saidas">
        <div class="stat-header">
            <div class="stat-icon">💸</div>
            <div class="stat-info">
                <div class="stat-label">Total Saídas - <?php echo $periodo_nome; ?></div>
                <div class="stat-value">R$ <?php echo number_format($total_periodo_saida, 2, ',', '.'); ?></div>
                <div class="stat-trend">Despesas totais do período</div>
            </div>
        </div>
    </div>

    <div class="stat-card saldo">
        <div class="stat-header">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-label">Saldo - <?php echo $periodo_nome; ?></div>
                <div class="stat-value">R$ <?php echo number_format($saldo_periodo, 2, ',', '.'); ?></div>
                <div class="stat-trend">Resultado líquido</div>
            </div>
        </div>
    </div>

    <div class="stat-card boletos">
        <div class="stat-header">
            <div class="stat-icon">📄</div>
            <div class="stat-info">
                <div class="stat-label">Total Boletos - <?php echo $periodo_nome; ?></div>
                <div class="stat-value"><?php echo number_format($total_periodo_boletos, 0, ',', '.'); ?></div>
                <div class="stat-trend">Pagamentos recebidos</div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="charts-grid">
    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title">📈 Entradas vs Saídas</div>
            <div class="chart-subtitle">Comparativo de <?php echo $periodo_nome; ?></div>
        </div>
        <div class="chart-wrapper">
            <canvas id="chartEntradasSaidas"></canvas>
        </div>
    </div>

    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title">💵 Saldo</div>
            <div class="chart-subtitle">Resultado líquido - <?php echo $periodo_nome; ?></div>
        </div>
        <div class="chart-wrapper">
            <canvas id="chartSaldo"></canvas>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title">🥧 Distribuição</div>
            <div class="chart-subtitle">Proporção de entradas e saídas - <?php echo $periodo_nome; ?></div>
        </div>
        <div class="chart-wrapper">
            <canvas id="chartPizza"></canvas>
        </div>
    </div>

    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title">📊 Boletos Pagos</div>
            <div class="chart-subtitle">Quantidade de pagamentos - <?php echo $periodo_nome; ?></div>
        </div>
        <div class="chart-wrapper">
            <canvas id="chartBoletos"></canvas>
        </div>
    </div>
</div>

<!-- Tabela de Detalhamento -->
<div class="monthly-table">
    <div class="chart-header">
        <div class="chart-title">📋 Detalhamento de <?php echo $periodo_nome; ?></div>
    </div>
    <table>
        <thead>
            <tr>
                <th><?php echo $mes_grafico > 0 ? 'Dia' : 'Mês'; ?></th>
                <th>Entradas</th>
                <th>Saídas</th>
                <th>Saldo</th>
                <th>Boletos</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($mes_grafico > 0) {
                // Exibir dados DIÁRIOS do mês
                $dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mes_grafico, $ano_grafico);
                
                for ($i = 0; $i < count($labels); $i++) {
                    $dia = $labels[$i];
                    $entrada = $dados_entradas[$i];
                    $saida = $dados_saidas[$i];
                    $saldo_dia = $dados_saldo[$i];
                    $boletos = $dados_boletos[$i];
                    
                    $saldo_color = $saldo_dia >= 0 ? 'color: #10B981; font-weight: bold;' : 'color: #EF4444; font-weight: bold;';
                    
                    echo "<tr>";
                    echo "<td style='font-weight: 600;'>Dia $dia</td>";
                    echo "<td style='color: #10B981; font-weight: 600;'>R$ " . number_format($entrada, 2, ',', '.') . "</td>";
                    echo "<td style='color: #EF4444; font-weight: 600;'>R$ " . number_format($saida, 2, ',', '.') . "</td>";
                    echo "<td style='$saldo_color'>R$ " . number_format($saldo_dia, 2, ',', '.') . "</td>";
                    echo "<td style='font-weight: 600;'>$boletos</td>";
                    echo "</tr>";
                }
            } else {
                // Exibir dados MENSAIS do ano
                $meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                
                for ($i = 0; $i < 12; $i++) {
                    $mes_nome = $meses_nomes[$i];
                    $entrada = $dados_entradas[$i];
                    $saida = $dados_saidas[$i];
                    $saldo_mes = $dados_saldo[$i];
                    $boletos = $dados_boletos[$i];
                    
                    $saldo_color = $saldo_mes >= 0 ? 'color: #10B981; font-weight: bold;' : 'color: #EF4444; font-weight: bold;';
                    
                    echo "<tr>";
                    echo "<td style='font-weight: 600;'>$mes_nome</td>";
                    echo "<td style='color: #10B981; font-weight: 600;'>R$ " . number_format($entrada, 2, ',', '.') . "</td>";
                    echo "<td style='color: #EF4444; font-weight: 600;'>R$ " . number_format($saida, 2, ',', '.') . "</td>";
                    echo "<td style='$saldo_color'>R$ " . number_format($saldo_mes, 2, ',', '.') . "</td>";
                    echo "<td style='font-weight: 600;'>$boletos</td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>

<!-- JavaScript dos Gráficos -->
<script>
    // Dados dos gráficos
    const labels = <?php echo $json_labels; ?>;
    const entradas = <?php echo $json_entradas; ?>;
    const saidas = <?php echo $json_saidas; ?>;
    const saldo = <?php echo $json_saldo; ?>;
    const boletos = <?php echo $json_boletos; ?>;

    let charts = {};

    function initCharts() {
        // Destroy existing charts if they exist
        Object.values(charts).forEach(chart => {
            if (chart) chart.destroy();
        });

        // Configurações comuns
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            }
        };

        // Gráfico Entradas vs Saídas
        const ctxEntradasSaidas = document.getElementById('chartEntradasSaidas');
        if (ctxEntradasSaidas) {
            charts.entradasSaidas = new Chart(ctxEntradasSaidas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Entradas',
                            data: entradas,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 2
                        },
                        {
                            label: 'Saídas',
                            data: saidas,
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    ...commonOptions,
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

        // Gráfico Saldo
        const ctxSaldo = document.getElementById('chartSaldo');
        if (ctxSaldo) {
            charts.saldo = new Chart(ctxSaldo.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Saldo',
                        data: saldo,
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
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

        // Gráfico Pizza
        const ctxPizza = document.getElementById('chartPizza');
        if (ctxPizza) {
            const totalEntradas = entradas.reduce((a, b) => a + b, 0);
            const totalSaidas = saidas.reduce((a, b) => a + b, 0);
            
            charts.pizza = new Chart(ctxPizza.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Entradas', 'Saídas'],
                    datasets: [{
                        data: [totalEntradas, totalSaidas],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': R$ ' + context.parsed.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }

        // Gráfico Boletos
        const ctxBoletos = document.getElementById('chartBoletos');
        if (ctxBoletos) {
            charts.boletos = new Chart(ctxBoletos.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Boletos Pagos',
                        data: boletos,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    }

    function atualizarGraficos() {
        const ano = document.getElementById('ano').value;
        const mes = document.getElementById('mes').value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('ano', ano);
        currentUrl.searchParams.set('mes', mes);
        window.location.href = currentUrl.toString();
    }

    // Auto-inicializar gráficos quando a página carregar (se estiver na aba de gráficos)
    document.addEventListener('DOMContentLoaded', function() {
        const graficosTab = document.getElementById('content-graficos');
        if (graficosTab && graficosTab.classList.contains('active')) {
            setTimeout(function() {
                if (typeof initCharts === 'function') {
                    initCharts();
                }
            }, 100);
        }
    });
</script>