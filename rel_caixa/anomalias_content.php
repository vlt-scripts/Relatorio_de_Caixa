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
// ARQUIVO: anomalias_content.php
// Este arquivo detecta anomalias nos pagamentos (valores divergentes do esperado)
// ==================================================================================

// Filtros
$ano_anomalia = isset($_GET['ano_anomalia']) ? intval($_GET['ano_anomalia']) : date('Y');
$mes_anomalia = isset($_GET['mes_anomalia']) ? intval($_GET['mes_anomalia']) : date('m');
$tipo_anomalia = isset($_GET['tipo_anomalia']) ? $_GET['tipo_anomalia'] : 'todos';
$incluir_adicionais = isset($_GET['incluir_adicionais']) ? $_GET['incluir_adicionais'] : 'nao';
?>

<div class="filter-section">
    <div class="filter-group">
        <label for="ano_anomalia">📅 Ano</label>
        <select id="ano_anomalia" name="ano_anomalia" onchange="atualizarAnomalias()">
            <?php
            $ano_atual = date('Y');
            for ($i = $ano_atual; $i >= $ano_atual - 10; $i--) {
                $selected = ($i == $ano_anomalia) ? 'selected' : '';
                echo "<option value='$i' $selected>$i</option>";
            }
            ?>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="mes_anomalia">📆 Mês</label>
        <select id="mes_anomalia" name="mes_anomalia" onchange="atualizarAnomalias()">
            <?php
            $meses_lista = [
                0 => 'Todos os Meses',
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
            ];
            foreach ($meses_lista as $num => $nome) {
                $selected = ($num == $mes_anomalia) ? 'selected' : '';
                echo "<option value='$num' $selected>$nome</option>";
            }
            ?>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="tipo_anomalia">🔍 Tipo de Anomalia</label>
        <select id="tipo_anomalia" name="tipo_anomalia" onchange="atualizarAnomalias()">
            <option value="todos" <?php echo $tipo_anomalia == 'todos' ? 'selected' : ''; ?>>Todas</option>
            <option value="pagamento_menor" <?php echo $tipo_anomalia == 'pagamento_menor' ? 'selected' : ''; ?>>Pagamento Menor</option>
            <option value="pagamento_maior" <?php echo $tipo_anomalia == 'pagamento_maior' ? 'selected' : ''; ?>>Pagamento Maior</option>
            <option value="divergencia_alta" <?php echo $tipo_anomalia == 'divergencia_alta' ? 'selected' : ''; ?>>Divergência Alta (&gt;50%)</option>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="incluir_adicionais">➕ Adicionais</label>
        <select id="incluir_adicionais" name="incluir_adicionais" onchange="atualizarAnomalias()">
            <option value="nao" <?php echo $incluir_adicionais == 'nao' ? 'selected' : ''; ?>>Ocultar</option>
            <option value="sim" <?php echo $incluir_adicionais == 'sim' ? 'selected' : ''; ?>>Mostrar</option>
        </select>
    </div>
    
    <div id="loading-anomalias" style="display: none; margin-left: 15px; align-self: flex-end;">
        <div class="loading-spinner"></div>
    </div>
</div>

<?php
// OTIMIZAÇÃO: Definir limite de registros baseado no filtro
// Aumentado para garantir que não perca anomalias importantes
$limit = ($mes_anomalia > 0) ? 1000 : 500;

// Query para buscar anomalias - OTIMIZADA
$where_conditions = "WHERE YEAR(c.data) = $ano_anomalia";

if ($mes_anomalia > 0) {
    $where_conditions .= " AND MONTH(c.data) = $mes_anomalia";
}

// OTIMIZAÇÃO: Extrair ID do título de forma mais eficiente
// Ordena por data DESC para pegar os mais recentes, mas SEM LIMIT na busca inicial
$query_anomalias = "
    SELECT 
        c.id,
        c.data,
        c.entrada as valor_pago,
        c.historico,
        c.usuario,
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(c.historico, 'titulo ', -1), ' ', 1) AS UNSIGNED) as titulo_id
    FROM sis_caixa c
    $where_conditions
    AND c.entrada > 0
    AND c.historico LIKE '%titulo %'
    ORDER BY c.data DESC
";

$result_anomalias = mysqli_query($link, $query_anomalias);

// OTIMIZAÇÃO: Coletar IDs dos títulos primeiro
$titulo_ids = [];
$caixa_data = [];

while ($row = mysqli_fetch_assoc($result_anomalias)) {
    $titulo_id = intval($row['titulo_id']);
    if ($titulo_id > 0) {
        $titulo_ids[] = $titulo_id;
        $caixa_data[$row['id']] = $row;
    }
}

// OTIMIZAÇÃO: Buscar dados dos títulos em uma única query
$anomalias_encontradas = [];

if (count($titulo_ids) > 0) {
    $titulo_ids_str = implode(',', $titulo_ids);
    
    // Construir query base
    $query_titulos = "
        SELECT 
            l.id as titulo_id,
            l.login,
            l.valor as valor_esperado,
            l.datavenc,
            cl.nome as cliente_nome,
            cl.uuid_cliente
        FROM sis_lanc l
        LEFT JOIN sis_cliente cl ON l.login = cl.login
        WHERE l.id IN ($titulo_ids_str)
        AND l.valor > 0
    "; 
    
    // Adicionar filtro de adicionais apenas se NÃO incluir
    if ($incluir_adicionais !== 'sim') {
        $query_titulos .= "
        AND l.id NOT IN (
            SELECT idlanc 
            FROM sis_mlanc 
            WHERE deltitulo = 0 
            AND valor > 0
        )
        ";
    }
    
    $result_titulos = mysqli_query($link, $query_titulos);
    
    // Criar índice de títulos para lookup rápido
    $titulos_index = [];
    while ($titulo = mysqli_fetch_assoc($result_titulos)) {
        $titulos_index[$titulo['titulo_id']] = $titulo;
    }
    
    // OTIMIZAÇÃO: Combinar dados e filtrar anomalias
    foreach ($caixa_data as $caixa_id => $caixa) {
        $titulo_id = intval($caixa['titulo_id']);
        
        if (isset($titulos_index[$titulo_id])) {
            $titulo = $titulos_index[$titulo_id];
            $valor_pago = floatval($caixa['valor_pago']);
            $valor_esperado = floatval($titulo['valor_esperado']);
            
            // Verificar se há divergência significativa
            if (abs($valor_pago - $valor_esperado) > 0.50) {
                $anomalias_encontradas[] = [
                    'caixa' => $caixa,
                    'titulo' => $titulo,
                    'valor_pago' => $valor_pago,
                    'valor_esperado' => $valor_esperado
                ];
            }
        }
    }
}

// Arrays para estatísticas
$anomalias = [];
$total_anomalias = 0;
$total_divergencia = 0;
$pagamentos_menores = 0;
$pagamentos_maiores = 0;
$divergencia_critica = 0;

foreach ($anomalias_encontradas as $item) {
    $caixa = $item['caixa'];
    $titulo = $item['titulo'];
    $valor_pago = $item['valor_pago'];
    $valor_esperado = $item['valor_esperado'];
    
    $diferenca = $valor_pago - $valor_esperado;
    $percentual = $valor_esperado > 0 ? (($diferenca / $valor_esperado) * 100) : 0;
    
    // Determinar tipo de anomalia
    $tipo = '';
    $severidade = '';
    
    if ($valor_pago < $valor_esperado) {
        $pagamentos_menores++;
        $tipo = 'menor';
        if (abs($percentual) > 50) {
            $severidade = 'critica';
            $divergencia_critica++;
        } elseif (abs($percentual) > 20) {
            $severidade = 'alta';
        } else {
            $severidade = 'media';
        }
    } else {
        $pagamentos_maiores++;
        $tipo = 'maior';
        if (abs($percentual) > 50) {
            $severidade = 'alta';
        } else {
            $severidade = 'media';
        }
    }
    
    // Aplicar filtro de tipo
    $incluir = false;
    switch ($tipo_anomalia) {
        case 'pagamento_menor':
            $incluir = ($tipo == 'menor');
            break;
        case 'pagamento_maior':
            $incluir = ($tipo == 'maior');
            break;
        case 'divergencia_alta':
            $incluir = (abs($percentual) > 50);
            break;
        default:
            $incluir = true;
    }
    
    if ($incluir) {
        $anomalias[] = [
            'data' => $caixa['data'],
            'cliente_nome' => $titulo['cliente_nome'] ?: 'N/A',
            'login' => $titulo['login'],
            'uuid_cliente' => $titulo['uuid_cliente'],
            'titulo_id' => $titulo['titulo_id'],
            'valor_esperado' => $valor_esperado,
            'valor_pago' => $valor_pago,
            'diferenca' => $diferenca,
            'percentual' => $percentual,
            'tipo' => $tipo,
            'severidade' => $severidade,
            'usuario' => $caixa['usuario'],
            'datavenc' => $titulo['datavenc']
        ];
        
        $total_anomalias++;
        $total_divergencia += abs($diferenca);
    }
}

// Ordenar por severidade e diferença
usort($anomalias, function($a, $b) {
    $ordem_sev = ['critica' => 0, 'alta' => 1, 'media' => 2];
    $sev_diff = $ordem_sev[$a['severidade']] - $ordem_sev[$b['severidade']];
    if ($sev_diff != 0) return $sev_diff;
    return abs($b['diferenca']) <=> abs($a['diferenca']);
});

// APLICAR LIMITE APENAS APÓS PROCESSAR TUDO
// Isso garante que pegamos as anomalias mais graves
if (count($anomalias) > $limit) {
    $anomalias = array_slice($anomalias, 0, $limit);
    $anomalias_limitadas = true;
} else {
    $anomalias_limitadas = false;
}

// Recalcular total_anomalias após o limite
$total_anomalias = count($anomalias);

// Calcular totais gerais para os cards (incluindo itens filtrados)
$total_geral = count($anomalias_encontradas);
$divergencia_total_geral = 0;
foreach ($anomalias_encontradas as $item) {
    $diferenca = abs($item['valor_pago'] - $item['valor_esperado']);
    $divergencia_total_geral += $diferenca;
}

$meses_nomes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$periodo_nome = $mes_anomalia > 0 ? $meses_nomes[$mes_anomalia] . ' de ' . $ano_anomalia : $ano_anomalia;
?>

<!-- Cards de Estatísticas de Anomalias -->
<div class="stats-grid">
    <div class="stat-card anomalia-total">
        <div class="stat-header">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
                <div class="stat-label">Total de Anomalias</div>
                <div class="stat-value"><?php echo number_format($total_anomalias, 0, ',', '.'); ?></div>
                <div class="stat-trend">
                    <?php if ($anomalias_limitadas): ?>
                        Exibindo top <?php echo $limit; ?> mais graves
                    <?php else: ?>
                        Pagamentos com divergência
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-card divergencia-total">
        <div class="stat-header">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-label">Divergência Total</div>
                <div class="stat-value">R$ <?php echo number_format($total_divergencia, 2, ',', '.'); ?></div>
                <div class="stat-trend">Soma das diferenças</div>
            </div>
        </div>
    </div>

    <div class="stat-card pagamentos-menores-card">
        <div class="stat-header">
            <div class="stat-icon">📉</div>
            <div class="stat-info">
                <div class="stat-label">Pagamentos Menores</div>
                <div class="stat-value"><?php echo number_format($pagamentos_menores, 0, ',', '.'); ?></div>
                <div class="stat-trend">Valor pago < valor esperado</div>
            </div>
        </div>
    </div>

    <div class="stat-card divergencia-critica-card">
        <div class="stat-header">
            <div class="stat-icon">🔴</div>
            <div class="stat-info">
                <div class="stat-label">Divergências Críticas</div>
                <div class="stat-value"><?php echo number_format($divergencia_critica, 0, ',', '.'); ?></div>
                <div class="stat-trend">Diferença &gt; 50%</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Anomalias Detalhada -->
<?php if (count($anomalias) > 0): ?>
<div class="anomalias-table">
    <div class="chart-header">
        <div class="chart-title">
            📋 Listagem Detalhada de Anomalias
            <?php if ($anomalias_limitadas): ?>
                <span style="font-size: 12px; font-weight: 500; color: #F59E0B; margin-left: 10px;">
                    ⚡ Exibindo <?php echo $limit; ?> anomalias mais graves do período
                </span>
            <?php endif; ?>
        </div>
        <button onclick="exportarAnomaliasPDF()" class="btn btn-secondary" style="margin-left: auto;">
            📥 Exportar PDF
        </button>
    </div>
    
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width: 110px;">📅 Data</th>
                    <th style="width: 150px;">👤 Cliente</th>
                    <th style="width: 100px;">🔑 Login</th>
                    <th style="width: 70px;">🆔 Título</th>
                    <th style="width: 100px;">💵 Esperado</th>
                    <th style="width: 100px;">💰 Pago</th>
                    <th style="width: 100px;">📊 Diferença</th>
                    <th style="width: 80px;">📈 %</th>
                    <th style="width: 100px;">⚠️ Severidade</th>
                    <th style="width: 100px;">👨‍💼 Usuário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anomalias as $anomalia): ?>
                <tr class="anomalia-row <?php echo $anomalia['severidade']; ?>">
                    <td style="font-weight: 600; font-size: 12px; white-space: nowrap;">
                        <?php echo date('d/m/Y', strtotime($anomalia['data'])); ?>
                        <br><small style="color: #6B7280; font-size: 10px;"><?php echo date('H:i', strtotime($anomalia['data'])); ?></small>
                    </td>
                    <td style="font-size: 12px;">
                        <?php if ($anomalia['uuid_cliente']): ?>
                            <a href="../../cliente_det.hhvm?uuid=<?php echo $anomalia['uuid_cliente']; ?>" target="_blank" style="font-size: 12px;">
                                <img src="img/icon_cliente.png" alt="Cliente" class="icon-sm">
                                <?php 
                                $nome_truncado = strlen($anomalia['cliente_nome']) > 18 
                                    ? substr($anomalia['cliente_nome'], 0, 18) . '...' 
                                    : $anomalia['cliente_nome'];
                                echo $nome_truncado;
                                ?>
                            </a>
                        <?php else: ?>
                            <?php echo $anomalia['cliente_nome']; ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 500; font-size: 12px;"><?php echo substr($anomalia['login'], 0, 20); ?></td>
                    <td>
                        <span class="badge badge-info" style="font-size: 11px; padding: 2px 6px;">
                            <?php echo $anomalia['titulo_id']; ?>
                        </span>
                    </td>
                    <td style="color: #3B82F6; font-weight: 600; font-size: 12px;">
                        R$ <?php echo number_format($anomalia['valor_esperado'], 2, ',', '.'); ?>
                    </td>
                    <td style="color: #10B981; font-weight: 700; font-size: 12px;">
                        R$ <?php echo number_format($anomalia['valor_pago'], 2, ',', '.'); ?>
                    </td>
                    <td>
                        <span class="diferenca-badge <?php echo $anomalia['tipo']; ?>" style="font-size: 11px; padding: 3px 8px;">
                            <?php echo ($anomalia['diferenca'] >= 0 ? '+' : '') . 'R$ ' . number_format($anomalia['diferenca'], 2, ',', '.'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="percentual-badge <?php echo $anomalia['tipo']; ?>" style="font-size: 11px; padding: 3px 8px;">
                            <?php echo ($anomalia['percentual'] >= 0 ? '+' : '') . number_format($anomalia['percentual'], 1, ',', '.'); ?>%
                        </span>
                    </td>
                    <td>
                        <?php
                        $severidade_icons = [
                            'critica' => '🔴',
                            'alta' => '🟠',
                            'media' => '🟡'
                        ];
                        $severidade_labels = [
                            'critica' => 'CRÍTICA',
                            'alta' => 'ALTA',
                            'media' => 'MÉDIA'
                        ];
                        ?>
                        <span class="severidade-badge <?php echo $anomalia['severidade']; ?>" style="font-size: 10px; padding: 4px 8px;">
                            <?php echo $severidade_icons[$anomalia['severidade']]; ?>
                            <?php echo $severidade_labels[$anomalia['severidade']]; ?>
                        </span>
                    </td>
                    <td style="font-weight: 500; font-size: 12px;"><?php echo substr($anomalia['usuario'], 0, 20); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="no-data-anomalias">
    <div class="no-data-icon">✅</div>
    <h3>Nenhuma Anomalia Detectada!</h3>
    <p>Não foram encontradas divergências significativas nos pagamentos do período selecionado.</p>
    <p style="color: #6B7280; font-size: 14px; margin-top: 10px;">
        Isso significa que todos os pagamentos estão alinhados com os valores esperados dos títulos.
    </p>
</div>
<?php endif; ?>

<style>
/* Cards específicos */
.stat-card.anomalia-total::before { background: #EF4444; }
.stat-card.divergencia-total::before { background: #F59E0B; }
.stat-card.pagamentos-menores-card::before { background: #8B5CF6; }
.stat-card.divergencia-critica-card::before { background: #DC2626; }

.stat-card.anomalia-total .stat-value { color: #EF4444; }
.stat-card.divergencia-total .stat-value { color: #F59E0B; }
.stat-card.pagamentos-menores-card .stat-value { color: #8B5CF6; }
.stat-card.divergencia-critica-card .stat-value { color: #DC2626; }

/* Tabela de Anomalias */
.anomalias-table {
    background: white;
    border-radius: 10px;
    padding: 20px;
    border: 1px solid var(--border);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-top: 25px;
}

.anomalias-table table {
    table-layout: fixed;
    width: 100%;
    border-collapse: collapse;
}

.anomalias-table th {
    padding: 10px 6px;
    font-size: 11px;
    white-space: nowrap;
    background: #F8FAFC;
    border-bottom: 2px solid #E5E7EB;
}

.anomalias-table td {
    padding: 8px 6px;
    font-size: 12px;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    border-bottom: 1px solid #F1F5F9;
}

/* CORREÇÃO COMPLETA DAS LINHAS - ESPECÍFICO PARA CLASSE MEDIA */
.anomalias-table tr.anomalia-row {
    display: table-row !important;
    width: 100% !important;
    table-layout: fixed !important;
}

.anomalias-table tr.anomalia-row.critica {
    background: #FEE2E2 !important;
    border-left: 4px solid #DC2626 !important;
}

.anomalias-table tr.anomalia-row.alta {
    background: #FEF3C7 !important;
    border-left: 4px solid #F59E0B !important;
}

.anomalias-table tr.anomalia-row.media {
    background: #FEF9C3 !important;
    border-left: 4px solid #FCD34D !important;
    display: table-row !important;
    width: 100% !important;
    table-layout: fixed !important;
}

.anomalias-table tr.anomalia-row.critica:hover {
    background: #FECACA !important;
}

.anomalias-table tr.anomalia-row.alta:hover {
    background: #FDE68A !important;
}

.anomalias-table tr.anomalia-row.media:hover {
    background: #FEF08A !important;
}

/* GARANTIR QUE AS CÉLULAS DAS LINHAS MEDIA TENHAM COMPORTAMENTO CORRETO */
.anomalias-table tr.anomalia-row.media td {
    display: table-cell !important;
    width: auto !important;
    padding: 8px 6px !important;
    font-size: 12px !important;
    vertical-align: middle !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    border-bottom: 1px solid #F1F5F9 !important;
    background: inherit !important;
}

/* CORREÇÃO DAS BADGES */
.diferenca-badge {
    display: inline-block !important;
    padding: 4px 10px !important;
    border-radius: 6px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-align: center !important;
    white-space: nowrap !important;
    min-width: 80px !important;
}

.diferenca-badge.menor {
    background: #FEE2E2 !important;
    color: #991B1B !important;
    border: 1px solid #FECACA !important;
}

.diferenca-badge.maior {
    background: #DBEAFE !important;
    color: #1E40AF !important;
    border: 1px solid #BFDBFE !important;
}

.percentual-badge {
    display: inline-block !important;
    padding: 4px 10px !important;
    border-radius: 6px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-align: center !important;
    white-space: nowrap !important;
    min-width: 70px !important;
}

.percentual-badge.menor {
    background: #DC2626 !important;
    color: white !important;
    border: 1px solid #B91C1C !important;
}

.percentual-badge.maior {
    background: #3B82F6 !important;
    color: white !important;
    border: 1px solid #2563EB !important;
}

.severidade-badge {
    display: inline-block !important;
    padding: 5px 12px !important;
    border-radius: 6px !important;
    font-size: 10px !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px !important;
    white-space: nowrap !important;
    text-align: center !important;
    min-width: 80px !important;
}

.severidade-badge.critica {
    background: #DC2626 !important;
    color: white !important;
    border: 1px solid #B91C1C !important;
}

.severidade-badge.alta {
    background: #F59E0B !important;
    color: white !important;
    border: 1px solid #D97706 !important;
}

.severidade-badge.media {
    background: #FCD34D !important;
    color: #78350F !important;
    border: 1px solid #F59E0B !important;
}

/* Insights de Anomalias */
.insights-anomalias {
    margin-top: 30px;
    padding: 25px;
    background: white;
    border-radius: 10px;
    border: 2px solid #E5E7EB;
}

.insights-grid-anomalias {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.insight-anomalia {
    display: flex;
    gap: 15px;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid;
}

.insight-anomalia.critica {
    background: #FEE2E2;
    border-left-color: #DC2626;
}

.insight-anomalia.warning {
    background: #FEF3C7;
    border-left-color: #F59E0B;
}

.insight-anomalia.info {
    background: #EFF6FF;
    border-left-color: #3B82F6;
}

.insight-anomalia.good {
    background: #F0FDF4;
    border-left-color: #10B981;
}

.insight-icon {
    font-size: 32px;
    flex-shrink: 0;
}

.insight-content {
    flex: 1;
}

.insight-content strong {
    display: block;
    font-size: 16px;
    color: #1F2937;
    margin-bottom: 8px;
}

.insight-content p {
    font-size: 14px;
    color: #374151;
    line-height: 1.6;
    margin: 0;
}

.insight-content .highlight {
    font-weight: 700;
    color: #DC2626;
}

/* No Data State */
.no-data-anomalias {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 10px;
    border: 2px solid #E5E7EB;
    margin-top: 20px;
}

.no-data-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.no-data-anomalias h3 {
    font-size: 24px;
    font-weight: 700;
    color: #10B981;
    margin-bottom: 10px;
}

.no-data-anomalias p {
    font-size: 16px;
    color: #374151;
}

/* Loading Spinner */
.loading-spinner {
    width: 30px;
    height: 30px;
    border: 3px solid #E5E7EB;
    border-top-color: #4F46E5;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Alert Info Box */
.alert-info-box {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #EFF6FF;
    border: 2px solid #3B82F6;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 20px;
}

.alert-icon {
    font-size: 28px;
    flex-shrink: 0;
}

.alert-content {
    flex: 1;
    font-size: 14px;
    color: #1E40AF;
    line-height: 1.6;
}

.alert-content strong {
    color: #1E3A8A;
    font-weight: 700;
}

.alert-content small {
    display: block;
    margin-top: 8px;
    color: #3B82F6;
    font-size: 13px;
}

@media (max-width: 768px) {
    .insights-grid-anomalias {
        grid-template-columns: 1fr;
    }
    
    .anomalias-table table {
        font-size: 10px;
    }
    
    .anomalias-table th,
    .anomalias-table td {
        padding: 4px 3px;
        font-size: 10px;
    }
    
    .anomalias-table th {
        font-size: 9px;
    }
    
    /* CORREÇÃO ESPECÍFICA PARA MOBILE - CLASSE MEDIA */
    .anomalias-table tr.anomalia-row.media td {
        padding: 4px 3px !important;
        font-size: 10px !important;
    }
    
    /* Ocultar colunas menos importantes em mobile */
    .anomalias-table th:nth-child(9),
    .anomalias-table td:nth-child(9),
    .anomalias-table th:nth-child(10),
    .anomalias-table td:nth-child(10) {
        display: none;
    }

    /* Ajustes para badges em mobile */
    .diferenca-badge,
    .percentual-badge,
    .severidade-badge {
        font-size: 9px !important;
        padding: 3px 6px !important;
        min-width: 60px !important;
    }
}

</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<script>
// Dados para os gráficos - removidos para simplificar interface

let chartsAnomalias = {};

function initAnomaliasCharts() {
    // Gráficos removidos - interface simplificada
    console.log('Aba de anomalias carregada');
}

// Função de atualização com escopo global
window.atualizarAnomalias = function() {
    // Mostrar loading
    const loading = document.getElementById('loading-anomalias');
    if (loading) loading.style.display = 'flex';
    
    const ano = document.getElementById('ano_anomalia').value;
    const mes = document.getElementById('mes_anomalia').value;
    const tipo = document.getElementById('tipo_anomalia').value;
    const adicionais = document.getElementById('incluir_adicionais').value;
    
    // Criar URL limpa sem duplicar parâmetros
    const baseUrl = window.location.origin + window.location.pathname;
    const newUrl = new URL(baseUrl);
    
    // Adicionar apenas os parâmetros necessários
    newUrl.searchParams.set('tab', 'anomalias');
    newUrl.searchParams.set('ano_anomalia', ano);
    newUrl.searchParams.set('mes_anomalia', mes);
    newUrl.searchParams.set('tipo_anomalia', tipo);
    newUrl.searchParams.set('incluir_adicionais', adicionais);
    
    window.location.href = newUrl.toString();
};

// Alias para compatibilidade
function atualizarAnomalias() {
    window.atualizarAnomalias();
}

function exportarAnomaliasPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); // landscape, milímetros, A4
    
    const periodo = '<?php echo addslashes($periodo_nome); ?>';
    const hoje = new Date().toLocaleDateString('pt-BR');
    
    // Título
    doc.setFontSize(16);
    doc.setFont(undefined, 'bold');
    doc.text('Relatório de Anomalias nos Pagamentos', 148, 15, { align: 'center' });
    
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    doc.text('Período: ' + periodo, 148, 22, { align: 'center' });
    doc.text('Gerado em: ' + hoje, 148, 27, { align: 'center' });
    
    // Resumo (Cards)
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('Resumo:', 14, 35);
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    let yPos = 40;
    
    const resumo = [
        'Total de Anomalias: <?php echo $total_anomalias; ?>',
        'Divergência Total: R$ <?php echo number_format($total_divergencia, 2, ",", "."); ?>',
        'Pagamentos Menores: <?php echo $pagamentos_menores; ?>',
        'Pagamentos Maiores: <?php echo $pagamentos_maiores; ?>',
        'Divergências Críticas: <?php echo $divergencia_critica; ?>'
    ];
    
    resumo.forEach(linha => {
        doc.text(linha, 14, yPos);
        yPos += 5;
    });
    
    // Tabela de Anomalias
    const tableData = [];
    
    <?php foreach ($anomalias as $a): ?>
    tableData.push([
        '<?php echo date("d/m/Y", strtotime($a["data"])); ?>',
        '<?php echo addslashes(substr($a["cliente_nome"], 0, 20)); ?>',
        '<?php echo addslashes(substr($a["login"], 0, 15)); ?>',
        '<?php echo $a["titulo_id"]; ?>',
        'R$ <?php echo number_format($a["valor_esperado"], 2, ",", "."); ?>',
        'R$ <?php echo number_format($a["valor_pago"], 2, ",", "."); ?>',
        'R$ <?php echo number_format($a["diferenca"], 2, ",", "."); ?>',
        '<?php echo number_format($a["percentual"], 1, ",", "."); ?>%',
        '<?php echo strtoupper($a["severidade"]); ?>'
    ]);
    <?php endforeach; ?>
    
    doc.autoTable({
        head: [['Data', 'Cliente', 'Login', 'ID', 'Esperado', 'Pago', 'Diferença', '%', 'Severidade']],
        body: tableData,
        startY: yPos + 5,
        styles: { 
            fontSize: 8,
            cellPadding: 2
        },
        headStyles: { 
            fillColor: [79, 70, 229],
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center'
        },
        columnStyles: {
            0: { cellWidth: 20 },
            1: { cellWidth: 40 },
            2: { cellWidth: 30 },
            3: { cellWidth: 15, halign: 'center' },
            4: { cellWidth: 25, halign: 'right' },
            5: { cellWidth: 25, halign: 'right' },
            6: { cellWidth: 25, halign: 'right' },
            7: { cellWidth: 20, halign: 'center' },
            8: { cellWidth: 22, halign: 'center' }
        },
        alternateRowStyles: { fillColor: [245, 245, 245] },
        margin: { top: 10, left: 14, right: 14 }
    });
    
    // Rodapé
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text(
            'Página ' + i + ' de ' + pageCount,
            doc.internal.pageSize.getWidth() / 2,
            doc.internal.pageSize.getHeight() - 10,
            { align: 'center' }
        );
    }
    
    // Salvar
    const nomeArquivo = 'anomalias_' + periodo.replace(/ /g, '_').toLowerCase() + '.pdf';
    doc.save(nomeArquivo);
}

// Auto-inicializar gráficos
document.addEventListener('DOMContentLoaded', function() {
    const anomaliasTab = document.getElementById('content-anomalias');
    if (anomaliasTab && anomaliasTab.classList.contains('active')) {
        setTimeout(initAnomaliasCharts, 100);
    }
});
</script>