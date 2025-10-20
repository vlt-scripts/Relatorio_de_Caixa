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

<!DOCTYPE html>
<html lang="pt-BR" class="has-navbar-fixed-top">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="iso-8859-1">
<title>MK-AUTH :: <?php echo $Manifest->{'name'}; ?></title>

<link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
<link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
<link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css" />

<script src="../../scripts/jquery.js"></script>
<script src="../../scripts/mk-auth.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    :root {
        --primary: #6e7aff;
        --primary-dark: #4ce65b;
        --success: #10B981;
        --danger: #EF4444;
        --info: #3B82F6;
        --warning: #F59E0B;
        --dark: #1F2937;
        --border: #E5E7EB;
        --bg-light: #F9FAFB;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #fffff 0%, #764ba2 100%);
        color: #333;
        line-height: 1.5;
    }

    .container {
        width: 100%;
        max-width: 10000px;
        margin: 15px auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        overflow: hidden;
    }

    /* Breadcrumb */
    .breadcrumb {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        padding: 12px 20px;
    }

    .breadcrumb ul {
        display: flex;
        list-style: none;
        gap: 8px;
        font-size: 13px;
        color: white;
    }

    .breadcrumb a {
        color: rgba(255,255,255,0.9);
        text-decoration: none;
    }

    .breadcrumb a:hover {
        color: white;
    }

    .breadcrumb .is-active a {
        color: white;
        font-weight: 600;
    }

    /* TABS SYSTEM */
    .tabs-container {
        background: white;
        border-bottom: 2px solid var(--border);
    }

    .tabs {
        display: flex;
        padding: 0 20px;
        gap: 0;
        align-items: center;
        justify-content: flex-start;
    }

    .tab-button {
        padding: 15px 25px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: #6B7280;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
    }

    .tab-button:not(:last-child)::after {
        content: '|';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #D1D5DB;
        font-size: 18px;
        font-weight: 300;
        padding: 0 10px;
    }

    .tab-button:hover {
        color: var(--primary);
        background: var(--bg-light);
    }

    .tab-button.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: var(--bg-light);
    }

    .tab-content {
        display: none;
        padding: 20px;
        animation: fadeIn 0.3s ease-in;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Content */
    .content-wrapper {
        padding: 15px 20px;
    }

    /* Form compacto */
    .search-form {
        background: var(--bg-light);
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 15px;
        border: 1px solid var(--border);
    }

    form {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    form label {
        font-weight: 600;
        font-size: 11px;
        color: var(--dark);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    input[type="text"],
    input[type="date"],
    select {
        padding: 8px 10px;
        border: 1px solid var(--border);
        border-radius: 5px;
        font-size: 13px;
        transition: all 0.2s;
        min-width: 180px;
    }

    input[type="text"]:focus,
    input[type="date"]:focus,
    select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
    }

    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: white;
        color: var(--dark);
        border: 1px solid var(--border);
    }

    .btn-secondary:hover {
        background: var(--bg-light);
    }

    /* Stats em linha horizontal - COMPACTO */
    .stats-bar {
        display: flex;
        gap: 15px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .stat-item {
        flex: 1;
        min-width: 180px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: white;
        border-radius: 6px;
        border-left: 3px solid;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .stat-item.boletos { border-left-color: var(--dark); }
    .stat-item.entradas { border-left-color: var(--info); }
    .stat-item.saidas { border-left-color: var(--danger); }
    .stat-item.saldo { border-left-color: var(--success); }

    .stat-icon {
        font-size: 20px;
        opacity: 0.8;
    }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        font-size: 10px;
        font-weight: 600;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 2px;
    }

    .stat-value {
        font-size: 18px;
        font-weight: 700;
    }

    .stat-item.boletos .stat-value { color: var(--dark); }
    .stat-item.entradas .stat-value { color: var(--info); }
    .stat-item.saidas .stat-value { color: var(--danger); }
    .stat-item.saldo .stat-value { color: var(--success); }

    /* Table - COMPACTO como original */
    .table-wrapper {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    th {
        padding: 12px 10px;
        text-align: left;
        color: white;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        border-bottom: 3px solid rgba(255,255,255,0.2);
    }

    th:hover {
        background: rgba(255,255,255,0.15);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    tbody tr {
        border-bottom: 1px solid var(--border);
        transition: background 0.15s;
    }

    tbody tr:hover {
        background: var(--bg-light);
    }

    tbody tr.highlight {
        background: #f8f9fa;
    }

    tbody tr.highlight:hover {
        background: #f1f3f5;
    }

    td {
        padding: 9px 8px;
        font-size: 13px;
    }

    td a {
        text-decoration: none;
        color: var(--primary);
        font-weight: 600;
        transition: color 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    td a:hover {
        color: var(--primary-dark);
    }

    .no-data {
        text-align: center;
        padding: 40px 20px;
        color: #9CA3AF;
    }

    .hidden {
        display: none;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-success {
        background: #D1FAE5;
        color: #065F46;
    }

    .badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    .badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .icon-sm {
        width: 16px;
        height: 16px;
        vertical-align: middle;
    }

    /* GRAFICOS STYLES */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        border: 2px solid var(--border);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .stat-card.entradas::before { background: var(--success); }
    .stat-card.saidas::before { background: var(--danger); }
    .stat-card.saldo::before { background: var(--info); }
    .stat-card.boletos::before { background: var(--warning); }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.12);
    }

    .stat-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .stat-card .stat-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .stat-card.entradas .stat-icon { background: rgba(16, 185, 129, 0.1); }
    .stat-card.saidas .stat-icon { background: rgba(239, 68, 68, 0.1); }
    .stat-card.saldo .stat-icon { background: rgba(59, 130, 246, 0.1); }
    .stat-card.boletos .stat-icon { background: rgba(245, 158, 11, 0.1); }

    .stat-info {
        flex: 1;
    }

    .stat-card .stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card .stat-value {
        font-size: 26px;
        font-weight: 700;
        margin-top: 5px;
    }

    .stat-card.entradas .stat-value { color: var(--success); }
    .stat-card.saidas .stat-value { color: var(--danger); }
    .stat-card.saldo .stat-value { color: var(--info); }
    .stat-card.boletos .stat-value { color: var(--warning); }

    .stat-trend {
        font-size: 11px;
        margin-top: 5px;
        color: #6B7280;
    }

    /* Chart Containers */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .chart-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid var(--border);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .chart-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border);
    }

    .chart-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-subtitle {
        font-size: 12px;
        color: #6B7280;
        margin-top: 5px;
    }

    .chart-wrapper {
        position: relative;
        height: 350px;
    }

    /* Monthly Table */
    .monthly-table {
        background: white;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid var(--border);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-top: 20px;
    }

    .monthly-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .monthly-table thead {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    }

    .monthly-table th {
        padding: 12px;
        color: white;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: left;
    }

    .monthly-table td {
        padding: 12px;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }

    .monthly-table tbody tr:hover {
        background: var(--bg-light);
    }

    /* Filter Section */
    .filter-section {
        background: var(--bg-light);
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 20px;
        border: 1px solid var(--border);
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-weight: 600;
        font-size: 11px;
        color: var(--dark);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .container {
            width: 100%;
            margin: 0;
            border-radius: 0;
        }

        .content-wrapper {
            padding: 12px;
        }

        form {
            flex-direction: column;
            align-items: stretch;
        }

        .form-group {
            width: 100%;
        }

        input[type="text"],
        input[type="date"] {
            width: 100%;
        }

        .stats-bar {
            flex-direction: column;
        }

        .stat-item {
            min-width: 100%;
        }

        table {
            font-size: 11px;
        }

        th, td {
            padding: 6px 4px;
        }

        .charts-grid {
            grid-template-columns: 1fr;
        }

        .chart-wrapper {
            height: 300px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .tabs {
            padding: 0 10px;
        }

        .tab-button {
            padding: 12px 20px;
            font-size: 12px;
        }
    }
</style>

</head>

<script>
    function clearSearch() {
        document.getElementById('search').value = '';
        document.getElementById('data_inicial').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('data_final').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('searchForm').submit();
    }

    window.onload = function() {
        toggleTarifaRows();
        
        // Verificar se deve abrir a aba de gráficos, ticket ou evolução
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        
        if (activeTab === 'graficos') {
            switchTab('graficos');
        } else if (activeTab === 'ticket') {
            switchTab('ticket');
        } else if (activeTab === 'evolucao') {
            switchTab('evolucao');
        }
    };

    function toggleTarifaRows() {
        var tarifaRows = document.querySelectorAll('tr.tarifa-row');
        var toggleButton = document.getElementById('toggleButton');

        tarifaRows.forEach(function(row) {
            row.classList.toggle('hidden');
        });

        if (toggleButton && toggleButton.innerText === 'MOSTRAR') {
            toggleButton.innerText = 'OCULTAR';
        } else if (toggleButton) {
            toggleButton.innerText = 'MOSTRAR';
        }
    }

    function switchTab(tabName) {
        // Remove active class from all tabs and contents
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Add active class to selected tab and content
        document.getElementById('tab-' + tabName).classList.add('active');
        document.getElementById('content-' + tabName).classList.add('active');
        
        // Atualizar URL com a aba ativa (sem recarregar a página)
        const currentUrl = new URL(window.location.href);
        if (tabName === 'graficos' || tabName === 'ticket' || tabName === 'evolucao') {
            currentUrl.searchParams.set('tab', tabName);
        } else {
            // Remove o parâmetro tab quando voltar para financeiro
            currentUrl.searchParams.delete('tab');
        }
        window.history.pushState({}, '', currentUrl);
        
        // Initialize charts based on active tab
        if (tabName === 'graficos' && typeof initCharts === 'function') {
            setTimeout(initCharts, 100);
        } else if (tabName === 'ticket' && typeof initTicketChart === 'function') {
            setTimeout(initTicketChart, 100);
        } else if (tabName === 'evolucao' && typeof initEvolucaoCharts === 'function') {
            setTimeout(initEvolucaoCharts, 100);
        }
    }
</script>

<body>
    <?php include('../../topo.php'); ?>

    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumbs">
            <ul>
                <li><a href="#">ADDON</a></li>
                <li class="is-active">
                    <a href="#" aria-current="page"><?php echo htmlspecialchars($manifestTitle . " - V " . $manifestVersion); ?></a>
                </li>
            </ul>
        </nav>

        <!-- TABS -->
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-button active" id="tab-financeiro" onclick="switchTab('financeiro')">
                    📋 FINANCEIRO
                </button>
                <button class="tab-button" id="tab-graficos" onclick="switchTab('graficos')">
                    📊 GRÁFICOS
                </button>
                <button class="tab-button" id="tab-ticket" onclick="switchTab('ticket')">
                    🎫 TICKET
                </button>
                <button class="tab-button" id="tab-evolucao" onclick="switchTab('evolucao')">
                    📈 EVOLUÇÃO
                </button>
            </div>
        </div>

        <?php include('config.php'); ?>

        <?php if ($acesso_permitido) : ?>

            <!-- TAB CONTENT: FINANCEIRO -->
            <div id="content-financeiro" class="tab-content active">
                <div class="content-wrapper">
                    <div class="search-form">
                        <form id="searchForm" method="GET">
                            <div class="form-group">
                                <label for="search">Buscar Cliente</label>
                                <input type="text" id="search" name="search" placeholder="Digite o Login do Cliente" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="data_inicial">Data Inicial</label>
                                <input type="date" id="data_inicial" name="data_inicial" value="<?php echo isset($_GET['data_inicial']) ? htmlspecialchars($_GET['data_inicial']) : date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="data_final">Data Final</label>
                                <input type="date" id="data_final" name="data_final" value="<?php echo isset($_GET['data_final']) ? htmlspecialchars($_GET['data_final']) : date('Y-m-d'); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Buscar</button>
                            <button type="button" onclick="clearSearch()" class="btn btn-secondary">Limpar</button>
                            <button type="button" id="toggleButton" onclick="toggleTarifaRows()" class="btn btn-secondary">Ocultar</button>
                        </form>
                    </div>

                    <?php
                    // Dados de conexão com o banco de dados já estão em config.php
                    $query = "SELECT c.entrada, c.saida, c.historico, c.data, c.usuario 
                              FROM sis_caixa c ";

                    if (!isset($_GET['data_inicial']) && !isset($_GET['data_final'])) {
                        $data_atual = date('Y-m-d');
                        $query .= " WHERE DATE(c.data) = '$data_atual'";
                    }
                    else if (isset($_GET['data_inicial']) && isset($_GET['data_final'])) {
                        $data_inicial = date('Y-m-d', strtotime($_GET['data_inicial']));
                        $data_final = date('Y-m-d', strtotime($_GET['data_final']));
                        $data_final .= ' 23:59:59';
                        $query .= " WHERE DATE(c.data) BETWEEN '$data_inicial' AND '$data_final'";
                    }

                    if (isset($_GET['search'])) {
                        $search_term = mysqli_real_escape_string($link, trim($_GET['search']));
                        $query .= " AND (c.historico LIKE '%$search_term%' OR c.usuario LIKE '%$search_term%' 
                        OR EXISTS (SELECT 1 FROM sis_lanc sl 
                                       WHERE c.historico REGEXP 'titulo ([0-9]+)' AND 
                                             CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(c.historico, 'titulo ', -1), ' ', 1) AS UNSIGNED) = sl.id AND 
                                             sl.login LIKE '%$search_term%'))";
                    }

                    $query .= " ORDER BY c.data DESC";
                    $result = mysqli_query($link, $query);

                    $tot_entrada = 0;
                    $total_boletos_ = 0;
                    $tot_saida = 0;

                    while ($row = mysqli_fetch_assoc($result)) {
                        $tot_entrada += $row['entrada'];
                        $tot_saida += $row['saida'];
                        if ($row['entrada'] > 0) {
                            $total_boletos_++;
                        }
                    }

                    $saldo = $tot_entrada - $tot_saida;
                    ?>

                    <div class="stats-bar">
                        <div class="stat-item boletos">
                            <div class="stat-icon">📊</div>
                            <div class="stat-content">
                                <div class="stat-label">Total Boletos</div>
                                <div class="stat-value"><?php echo $total_boletos_; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item entradas">
                            <div class="stat-icon">💰</div>
                            <div class="stat-content">
                                <div class="stat-label">Total Entradas</div>
                                <div class="stat-value">R$ <?php echo number_format($tot_entrada, 2, ',', '.'); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item saidas">
                            <div class="stat-icon">💸</div>
                            <div class="stat-content">
                                <div class="stat-label">Total Saídas</div>
                                <div class="stat-value">R$ <?php echo number_format($tot_saida, 2, ',', '.'); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item saldo">
                            <div class="stat-icon">✅</div>
                            <div class="stat-content">
                                <div class="stat-label">Saldo</div>
                                <div class="stat-value">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($result && mysqli_num_rows($result) > 0) : ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>👤 Nome do Cliente</th>
                                        <th>🔑 Login</th>
                                        <th>📅 Data</th>
                                        <th>👨‍💼 Usuário</th>
                                        <th>📝 Histórico</th>
                                        <th>💵 Entrada</th>
                                        <th>💸 Saída</th>
                                        <th>🆔 ID</th>
                                        <th>📄 Boleto Pago</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php mysqli_data_seek($result, 0); ?>
                                    <?php $rowNumber = 0; ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <?php
                                        $nomeClienteClass = ($rowNumber % 2 == 0) ? 'highlight' : '';
                                        $tarifaRowClass = (strpos($row['historico'], 'Tarifa do GerenciaNet') !== false) ? 'tarifa-row' : '';
                                        ?>
                                        <tr class="<?php echo $nomeClienteClass . ' ' . $tarifaRowClass; ?>">
                                            <td>
                                                <?php
                                                preg_match('/titulo (\d+)/', $row['historico'], $matches);
                                                $id = isset($matches[1]) ? $matches[1] : '--';

                                                $cliente_query = "SELECT c.nome, l.login, c.uuid_cliente FROM sis_lanc l 
                                                JOIN sis_cliente c ON l.login = c.login
                                                WHERE l.id = '$id'";
                                                $cliente_result = mysqli_query($link, $cliente_query);
                                                $cliente_row = mysqli_fetch_assoc($cliente_result);
                                                $nome_cliente = isset($cliente_row['nome']) ? $cliente_row['nome'] : '--';
                                                $login = isset($cliente_row['login']) ? $cliente_row['login'] : '--';
                                                $uuid_cliente = isset($cliente_row['uuid_cliente']) ? $cliente_row['uuid_cliente'] : '';

                                                $max_length = 20;
                                                $nome_cliente_truncado = strlen($nome_cliente) > $max_length ? substr($nome_cliente, 0, $max_length) . '...' : $nome_cliente;

                                                echo '<a href="../../cliente_det.hhvm?uuid=' . $uuid_cliente . '" target="_blank" title="' . $nome_cliente . '">';
                                                echo '<img src="img/icon_cliente.png" alt="Ícone" class="icon-sm">';
                                                echo $nome_cliente_truncado;
                                                echo '</a>';
                                                ?>
                                            </td>

                                            <td>
                                                <?php
                                                $data_inicial = (!empty($_GET['data_inicial'])) ? date('Y-m-d', strtotime($_GET['data_inicial'])) : date('Y-m-d');
                                                $data_final = (!empty($_GET['data_final'])) ? date('Y-m-d', strtotime($_GET['data_final'])) : date('Y-m-d');
                                                $link_busca = "?search=" . urlencode($login) . "&data_inicial=" . urlencode($data_inicial) . "&data_final=" . urlencode($data_final);
                                                $max_length = 15;
                                                $login_truncado = (strlen($login) > $max_length) ? substr($login, 0, $max_length) . '...' : $login;
                                                ?>
                                                <a href="<?php echo $link_busca; ?>" title="<?php echo htmlspecialchars($login); ?>"><?php echo $login_truncado; ?></a>
                                            </td>

                                            <td style="font-weight: 500;"><?php echo date('d-m-Y H:i:s', strtotime($row['data'])); ?></td>
                                            <td style="font-weight: 500;"><?php echo $row['usuario']; ?></td>

                                            <td>
                                                <?php
                                                $max_length = 32;
                                                $historico = $row['historico'];
                                                $historico_abreviado = strlen($historico) > $max_length ? substr($historico, 0, $max_length) . '...' : $historico;
                                                ?>
                                                <span style="color: #059669; font-weight: 500;" title="<?php echo htmlspecialchars($historico); ?>">
                                                    <?php echo htmlspecialchars($historico_abreviado); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($row['entrada'] > 0): ?>
                                                    <span class="badge badge-success">R$ <?php echo number_format($row['entrada'], 2, ',', '.'); ?></span>
                                                <?php else: ?>
                                                    --
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if ($row['saida'] > 0): ?>
                                                    <span class="badge badge-danger">R$ <?php echo number_format($row['saida'], 2, ',', '.'); ?></span>
                                                <?php else: ?>
                                                    --
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <span class="badge badge-info">
                                                    <img src="img/digital.png" alt="ID" class="icon-sm"> <?php echo $id; ?>
                                                </span>
                                            </td>

                                            <td style="font-weight: 500;">
                                                <?php
                                                $id = isset($matches[1]) ? $matches[1] : '';
                                                $boleto_query = "SELECT datavenc FROM sis_lanc WHERE id = '$id'";
                                                $boleto_result = mysqli_query($link, $boleto_query);
                                                $datavenc = mysqli_fetch_assoc($boleto_result)['datavenc'];
                                                if ($datavenc && $datavenc != '0000-00-00') {
                                                    echo date('d-m-Y', strtotime($datavenc));
                                                } else {
                                                    echo '<span style="color: #9CA3AF;">--</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php $rowNumber++; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else : ?>
                        <p class="no-data">Nenhum resultado encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB CONTENT: GRÁFICOS -->
            <div id="content-graficos" class="tab-content">
                <div class="content-wrapper">
                    <?php 
                    if (file_exists('graficos_content.php')) {
                        include('graficos_content.php'); 
                    } else {
                        echo '<p class="no-data">Arquivo graficos_content.php não encontrado.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- TAB CONTENT: TICKET -->
            <div id="content-ticket" class="tab-content">
                <div class="content-wrapper">
                    <?php 
                    if (file_exists('ticket_content.php')) {
                        include('ticket_content.php'); 
                    } else {
                        echo '<p class="no-data">Arquivo ticket_content.php não encontrado.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- TAB CONTENT: EVOLUÇÃO -->
            <div id="content-evolucao" class="tab-content">
                <div class="content-wrapper">
                    <?php 
                    if (file_exists('evolucao_content.php')) {
                        include('evolucao_content.php'); 
                    } else {
                        echo '<p class="no-data">Arquivo evolucao_content.php não encontrado.</p>';
                    }
                    ?>
                </div>
            </div>

        <?php else : ?>
            <p class="no-data">🚫 Acesso não permitido!</p>
        <?php endif; ?>

        <?php include('../../baixo.php'); ?>
    </div>

    <script src="../../menu.js.php"></script>
    <?php include('../../rodape.php'); ?>

</body>
</html>