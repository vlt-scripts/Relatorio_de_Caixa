<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
include('addons.class.php');

// VERIFICA SE O USUARIO ESTA LOGADO --------------------------------------------------------------
session_name('mka');
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['mka_logado']) && !isset($_SESSION['MKA_Logado'])) exit('Acesso negado... <a href="/admin/login.php">Fazer Login</a>');
// VERIFICA SE O USUARIO ESTA LOGADO --------------------------------------------------------------

// Assuming $Manifest is defined somewhere before this code
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

<style>
    /* Estilos CSS personalizados */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #ffffff;
        margin: 0;
        padding: 0;
        color: #333;
    }

    .container {
        width: 100%;
        max-width: 1600px; /* Aumentando a largura máxima */
        margin: 20px auto;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    form {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    form label {
        font-weight: bold;
        margin-right: 10px;
    }

    .search-group {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    input[type="text"],
    input[type="date"],
    input[type="submit"],
    .clear-button {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    input[type="submit"],
    .clear-button {
        background-color: #007bff;
        color: #fff;
        cursor: pointer;
    }

    input[type="submit"]:hover,
    .clear-button:hover {
        background-color: #0056b3;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        padding: 9px; /* Aumentando o padding para melhor legibilidade */
        text-align: left;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
    }

    th {
        background-color: #007bff;
        color: #fff;
    }

    .no-data {
        text-align: center;
        color: #777;
        padding: 20px;
    }

    .total-section {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        padding: 10px;
        border-radius: 5px;
        background-color: #e4e4e4;
        color: #fff;
    }

    .total-section .total {
        font-weight: bold;
    }

    .hidden {
        display: none;
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

// Esconde os elementos tarifa-row quando a página é carregada
window.onload = function() {
    toggleTarifaRows();
};

function toggleTarifaRows() {
    var tarifaRows = document.querySelectorAll('tr.tarifa-row');
    var toggleButton = document.getElementById('toggleButton');

    tarifaRows.forEach(function(row) {
        row.classList.toggle('hidden');
    });

    if (toggleButton.innerText === 'Mostrar') {
        toggleButton.innerText = 'Ocultar';
    } else {
        toggleButton.innerText = 'Mostrar';
    }
}

    </script>

<body>
    <?php include('../../topo.php'); ?>

    <div class="container">
        <nav class="breadcrumb has-bullet-separator is-centered" aria-label="breadcrumbs">
            <ul>
                <li><a href="#"> ADDON</a></li>
                <li class="is-active">
                    <a href="#" aria-current="page"> <?php echo htmlspecialchars($manifestTitle . " - V " . $manifestVersion); ?> </a>
                </li>
            </ul>
        </nav>

        <?php include('config.php'); ?>

        <?php if ($acesso_permitido) : ?>
            <form id="searchForm" method="GET">
                <label for="search">Buscar Cliente:</label>
                <input type="text" id="search" name="search" placeholder="Digite o Login do Cliente" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <label for="data_inicial">Data Inicial:</label>
                <input type="date" id="data_inicial" name="data_inicial" value="<?php echo isset($_GET['data_inicial']) ? htmlspecialchars($_GET['data_inicial']) : date('Y-m-d'); ?>">
                <label for="data_final">Data Final:</label>
                <input type="date" id="data_final" name="data_final" value="<?php echo isset($_GET['data_final']) ? htmlspecialchars($_GET['data_final']) : date('Y-m-d'); ?>">
                <input type="submit" value="Buscar">
                <button type="button" onclick="clearSearch()" class="clear-button">Limpar</button>
                <button type="button" onclick="var tarifaRows = document.querySelectorAll('tr.tarifa-row'); tarifaRows.forEach(function(row) { row.classList.toggle('hidden'); }); if (this.innerText === 'Ocultar') { this.innerText = 'Mostrar'; } else { this.innerText = 'Ocultar'; }" class="clear-button sort-button-1">Mostrar</button>

            </form>

            <?php
            // Dados de conexão com o banco de dados já estão em config.php
            // Consulta SQL para obter as entradas e saídas no caixa com histórico, data e usuário
            $query = "SELECT c.entrada, c.saida, c.historico, c.data, c.usuario 
                      FROM sis_caixa c ";

            // Se datas não foram fornecidas no formulário de pesquisa, adicione condições à consulta SQL para buscar pela data atual
            if (!isset($_GET['data_inicial']) && !isset($_GET['data_final'])) {
                $data_atual = date('Y-m-d');
                $query .= " WHERE DATE(c.data) = '$data_atual'";
            }
            // Se datas foram fornecidas no formulário de pesquisa, adicione condições à consulta SQL
            else if (isset($_GET['data_inicial']) && isset($_GET['data_final'])) {
                $data_inicial = date('Y-m-d', strtotime($_GET['data_inicial']));
                $data_final = date('Y-m-d', strtotime($_GET['data_final']));
                $data_final .= ' 23:59:59'; // Incluir eventos que ocorram até o final do dia
                $query .= " WHERE DATE(c.data) BETWEEN '$data_inicial' AND '$data_final'";
            }

            // Se um termo de pesquisa foi fornecido, adicione condições à consulta SQL
            if (isset($_GET['search'])) {
            $search_term = mysqli_real_escape_string($link, trim($_GET['search'])); // Remover espaços em branco no início e no final
            $query .= " AND (c.historico LIKE '%$search_term%' OR c.usuario LIKE '%$search_term%' 
            OR EXISTS (SELECT 1 FROM sis_lanc sl 
                           WHERE c.historico REGEXP 'titulo ([0-9]+)' AND 
                                 CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(c.historico, 'titulo ', -1), ' ', 1) AS UNSIGNED) = sl.id AND 
                                 sl.login LIKE '%$search_term%'))";
            }

            // Adicionando a cláusula ORDER BY para ordenar por data mais recente
            $query .= " ORDER BY c.data DESC";

            // Execute a consulta
            $result = mysqli_query($link, $query);

            // Calcular o total de entradas e o número total de boletos
            $tot_entrada = 0;
            $total_boletos_ = 0; // Inicializando a contagem
            $tot_saida = 0;

            // Loop através dos resultados e somar as entradas e saídas
            while ($row = mysqli_fetch_assoc($result)) {
            $tot_entrada += $row['entrada'];
            $tot_saida += $row['saida'];
    
            // Verificar se a entrada é um boleto
            if ($row['entrada'] > 0) {
            $total_boletos_++; // Incrementar a contagem de boletos apenas para entradas
            }
            }

            // Calcular o saldo como antes
            $saldo = $tot_entrada - $tot_saida;

            ?>

            <div class="total-section" style="font-size: 20px;">
			    <div class="total" style="color: black;">Total Boletos: <?php echo $total_boletos_; ?></div><!-- Adicionando a contagem de boletos -->
                <div class="total" style="color: blue;">Total Entradas: R$ <?php echo number_format($tot_entrada, 2, ',', '.'); ?></div>
                <div class="total" style="color: red;">Total Saídas: R$ <?php echo number_format($tot_saida, 2, ',', '.'); ?></div>
                <div class="total" style="color: green;">Saldo: R$ <?php echo number_format($saldo, 2, ',', '.'); ?></div>				
            </div>



            <?php if ($result && mysqli_num_rows($result) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th style="color: white;">Nome do Cliente</th>
                            <th style="color: white;">Login</th>
                            <th style="color: white;">Data</th>
                            <th style="color: white;">Usuário</th>
                            <th style="color: white;">Histórico</th>
                            <th style="color: white;">Entrada</th>
                            <th style="color: white;">Saída</th>
                            <th style="color: white;">ID</th>
							<th style="color: white;">Boleto Pago</th> <!-- Nova coluna -->

                        </tr>
                    </thead>
                    <tbody>
                        <?php mysqli_data_seek($result, 0); // Voltar ao início do resultado ?>
                        <?php $rowNumber = 0; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <?php
                            // Adicionar a classe "highlight" a cada segunda linha
                            $nomeClienteClass = ($rowNumber % 2 == 0) ? 'highlight' : '';
                            $tarifaRowClass = (strpos($row['historico'], 'Tarifa do GerenciaNet') !== false) ? 'tarifa-row' : '';
                            ?>
                            <tr class="<?php echo $nomeClienteClass . ' ' . $tarifaRowClass; ?>">
							
                                <!--Exibe Nome do Cliente	 -->
                            <td style="text-align: left; cursor: default;">
                                <?php
                                // Encontrar o ID no histórico usando expressão regular
                                preg_match('/titulo (\d+)/', $row['historico'], $matches);
                                $id = isset($matches[1]) ? $matches[1] : '--';

                                // Consulta SQL para obter o login e uuid_cliente com base no ID
                                $cliente_query = "SELECT c.nome, l.login, c.uuid_cliente FROM sis_lanc l 
                                JOIN sis_cliente c ON l.login = c.login
                                WHERE l.id = '$id'";
                                $cliente_result = mysqli_query($link, $cliente_query);
                                $cliente_row = mysqli_fetch_assoc($cliente_result);
                                $nome_cliente = isset($cliente_row['nome']) ? $cliente_row['nome'] : '--';
                                $login = isset($cliente_row['login']) ? $cliente_row['login'] : '--';
                                $uuid_cliente = isset($cliente_row['uuid_cliente']) ? $cliente_row['uuid_cliente'] : '';

                                // Limitar o tamanho do nome do cliente
                                $max_length = 20; // Defina o comprimento máximo desejado
                                $nome_cliente_truncado = strlen($nome_cliente) > $max_length ? substr($nome_cliente, 0, $max_length) . '...' : $nome_cliente;

                                // Exibir o nome do cliente e link
                                echo '<a href="../../cliente_det.hhvm?uuid=' . $uuid_cliente . '" target="_blank" style="color: #06683e; display: flex; align-items: center;" title="' . $nome_cliente . '">';
                                echo '<img src="img/icon_cliente.png" alt="Ícone Digital" style="width: 20px; height: 20px; margin-right: 10px; float: left;">';
                                echo '<span style="color: #0d6cea; font-weight: bold;">' . $nome_cliente_truncado . '</span>';
                                echo '</a>';
                                ?>
                                </td>

                                <!-- Exibir LOGIN-->
                                <td style="color:#0d6cea; font-weight: bold;">
                                   <?php
                                   // Verificar se $_GET['data_inicial'] está definido e não está vazio
                                   $data_inicial = (!empty($_GET['data_inicial'])) ? date('Y-m-d', strtotime($_GET['data_inicial'])) : date('Y-m-d');

                                   // Verificar se $_GET['data_final'] está definido e não está vazio
                                   $data_final = (!empty($_GET['data_final'])) ? date('Y-m-d', strtotime($_GET['data_final'])) : date('Y-m-d');

                                   // Construir o link de busca com as datas formatadas
                                   $link_busca = "?search=" . urlencode($login) . "&data_inicial=" . urlencode($data_inicial) . "&data_final=" . urlencode($data_final);

                                   // Definir o comprimento máximo desejado para o nome de login
                                   $max_length = 15;

                                   // Limitar o tamanho do nome de login, se necessário
                                   $login_truncado = (strlen($login) > $max_length) ? substr($login, 0, $max_length) . '...' : $login;
                                   ?>
                                   <a href="<?php echo $link_busca; ?>" title="<?php echo htmlspecialchars($login); ?>" style="text-decoration: none; color: inherit;"><?php echo $login_truncado; ?></a>
                                </td>

                                <!--Exibe Data --> 
                                <td style="font-weight: bold;"><?php echo date('d-m-Y H:i:s', strtotime($row['data'])); ?></td> <!-- Data -->

                                <!--Exibe Usuario -->
                                <td style="font-weight: bold;"><?php echo $row['usuario']; ?></td> <!-- Usuario -->

                                <!-- Exibe Historico -->
                                <td style="position: relative; cursor: pointer;">
                                <?php
                                $max_length = 32; // Defina o comprimento máximo desejado
                                $historico = $row['historico'];
                                // Limita o tamanho do histórico e adiciona "..." se for muito longo
                                $historico_abreviado = strlen($historico) > $max_length ? substr($historico, 0, $max_length) . '...' : $historico;

                                // Destaque "nome_cliente" no histórico
                                $highlighted_historico = str_replace('nome_cliente', '<span class="highlight">nome_cliente</span>', $historico_abreviado);
                                ?>
                                <span style="color: #02824b; font-weight: bold; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: 200px;" title="<?php echo htmlspecialchars($historico); ?>">
                                <?php echo $highlighted_historico; ?>
                                </span>
                                <?php if (strlen($historico) > $max_length): ?>
                                <span style="position: absolute; right: 0; top: 50%; transform: translateY(-50%);">...</span>
                                <?php endif; ?>
                                </td>

                                <!--Exibe Entrada -->
                                <td style="color: green; font-weight: bold;"><?php echo $row['entrada']; ?></td>

                                <!--Exibe Saida -->
                                <td style="color: #ea1d0d; font-weight: bold;"><?php echo $row['saida']; ?></td>

                                <!--Exibe ID -->
                                <td style="color: #0471e7; font-weight: bold; text-align: center;">
                                    <img src="img/digital.png" alt="Ícone Digital" style="width: 20px; height: 20px; margin-right: 5px; float: left;">
                                    <?php echo $id; ?>
                                </td>
								
								<!-- Exibe Boleto Pago -->
                                <td style="font-weight: bold;">
                                <?php
                                // Consulta SQL para obter a data de vencimento do boleto da tabela sis_lanc
                                $id = isset($matches[1]) ? $matches[1] : '';
                                $boleto_query = "SELECT datavenc FROM sis_lanc WHERE id = '$id'";
                                $boleto_result = mysqli_query($link, $boleto_query);
                                $datavenc = mysqli_fetch_assoc($boleto_result)['datavenc'];
                                // Verifica se a data de vencimento é válida
                                if ($datavenc && $datavenc != '0000-00-00') {
                                // Exibir a data de vencimento do boleto
                                echo date('d-m-Y', strtotime($datavenc)); // Ajuste o formato da data conforme necessário
                                } else {
                                echo '<span style="color: #0d6cea;">--</span>'; // Se não for válida, exibe "--" em azul
                                }
                                ?>
                                </td>

                            </tr>
                            <?php $rowNumber++; ?>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php else : ?>
                <p class="no-data">Nenhum resultado encontrado.</p>
            <?php endif; ?>
        <?php else : ?>
            <p class="no-data">Acesso não permitido!</p>
        <?php endif; ?>

        <?php include('../../baixo.php'); ?>
    </div>

    <script src="../../menu.js.php"></script>
    <?php include('../../rodape.php'); ?>

</body>

</html>
