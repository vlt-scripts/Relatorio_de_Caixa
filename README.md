# 📊 Relatório de Caixa

> Addon avançado para MK-AUTH que transforma dados brutos de caixa em insights financeiros visuais e acionáveis.

## 🎯 Visão Geral

O **Relatório de Caixa** é um addon completo para o sistema MK-AUTH que resolve uma limitação importante: o log padrão do sistema não identifica claramente qual cliente efetuou cada pagamento, mostrando apenas mensagens genéricas como "*baixou o titulo 001 por pagamento*".

Este addon oferece uma solução robusta com múltiplas visualizações e análises financeiras detalhadas.

## ✨ Principais Recursos

### 📋 **1. Aba FINANCEIRO** - Sua lista de transações
*"Onde você vê quem pagou o que e quando"*

Imagine uma planilha bem organizada que mostra:
- **Quem pagou**: Nome completo do cliente (não apenas "baixou titulo 001")
- **Quando pagou**: Data e hora exata
- **Quanto pagou**: Valores de entrada e saída coloridos para fácil visualização
- **Buscar rápido**: Digite o nome de um cliente e veja só os pagamentos dele
- **Filtrar por período**: Escolha datas inicial e final para ver apenas o que interessa
- **Resumo no topo**: Caixinhas coloridas mostrando total de boletos, entradas, saídas e saldo

**Em resumo:** É como seu caderno de controle financeiro, mas digital e automático! 📒

---

### 📊 **2. Aba GRÁFICOS** - Visualize seu dinheiro
*"Números viram desenhos fáceis de entender"*

Transforma números chatos em gráficos bonitos:
- **Escolha o período**: Veja um mês específico (dia a dia) ou o ano todo (mês a mês)
- **4 gráficos coloridos**:
  - 📊 Barras verdes e vermelhas = quanto entrou vs quanto saiu
  - 📈 Linha azul = seu lucro ao longo do tempo
  - 🥧 Pizza = proporção entre ganhos e gastos
  - 📉 Barras amarelas = quantos boletos foram pagos
- **Tabela embaixo**: Todos os números organizadinhos caso você queira conferir

**Em resumo:** Seu dinheiro contado através de desenhos que fazem sentido! 🎨

---

### 🎫 **3. Aba TICKET MÉDIO** - Quanto vale cada cliente
*"Descubra se seus clientes estão pagando mais ou menos"*

Calcula automaticamente quanto cada pagamento vale em média:
- **Valor médio**: Quanto em média cada cliente paga (ex: R$ 89,90)
- **Comparação inteligente**: Compara com o ano passado automaticamente
  - "Está R$ 10 mais caro que ano passado" = 🟢 Ótimo!
  - "Está R$ 5 mais barato que ano passado" = 🟡 Atenção!
- **Melhor e pior dia/mês**: Mostra quando você recebeu mais e menos
- **Dicas automáticas**: O sistema sugere se está bom ou se precisa melhorar

**Exemplo prático:** 
- Você recebeu R$ 5.000 de 100 clientes = Ticket médio de R$ 50
- Ano passado era R$ 45 = Você melhorou! 🎉

**Em resumo:** Descubra se está ganhando mais ou menos por cliente! 💰

---

### 📈 **4. Aba EVOLUÇÃO** - Sua história de sucesso
*"Veja como seu negócio cresceu ao longo dos anos"*

Mostra todo o histórico desde 2015 até hoje:
- **CAGR**: Um número mágico que mostra se você cresceu bem (quanto maior, melhor!)
  - Acima de 15% = 🚀 Excelente crescimento!
  - Entre 5-15% = ✅ Crescimento saudável
  - Abaixo de 5% = ⚠️ Pode melhorar
- **Seu melhor ano**: Mostra qual ano você mais faturou (com troféu 🏆)
- **Projeção futura**: Calcula quanto você deve faturar no próximo ano
- **Gráficos de longo prazo**: Linhas mostrando como tudo evoluiu ano após ano
- **Insights inteligentes**: Mensagens dizendo se você está no caminho certo

**Exemplo prático:**
- 2020: R$ 50.000
- 2021: R$ 60.000 (+20%)
- 2022: R$ 75.000 (+25%)
- 2023: R$ 90.000 (+20%)
- **CAGR = 21%** = Crescimento excepcional! 🎊

**Em resumo:** É como ver seu negócio crescer em câmera lenta ao longo dos anos! 📈

## 🚀 Instalação

1. **Configure as permissões**
```bash
chmod 755 /opt/mk-auth/admin/addons/rel_caixa
chmod 755 /opt/mk-auth/admin/addons/rel_caixa/img
```

## 📖 Como Usar

### Tela Principal (Financeiro)

1. **Buscar Transações**
   - Digite o login do cliente no campo de busca
   - Selecione o período desejado (data inicial e final)
   - Clique em "Buscar"

2. **Visualizar Detalhes**
   - Clique no nome do cliente para ver o cadastro completo
   - Clique no login para filtrar todas as transações daquele cliente
   - Use o botão "Ocultar/Mostrar" para esconder tarifas do gateway

### Análise de Gráficos

1. Acesse a aba **"GRÁFICOS"**
2. Selecione o ano desejado
3. Escolha um mês específico ou "Todos os Meses"
4. Clique em "Atualizar" para visualizar os dados

### Análise de Ticket Médio

1. Acesse a aba **"TICKET"**
2. Selecione o ano e mês para análise
3. Visualize as comparações automáticas com o período anterior
4. Analise os insights gerados automaticamente

### Análise de Evolução

1. Acesse a aba **"EVOLUÇÃO"**
2. Visualize automaticamente:
   - CAGR do seu negócio
   - Melhor ano histórico
   - Projeção para o próximo ano
   - Insights de crescimento

## 🎨 Estrutura de Arquivos

```
rel_caixa/
├── index.php               # Arquivo principal com estrutura de abas
├── config.php              # Configurações de banco de dados
├── graficos_content.php    # Conteúdo da aba de gráficos
├── ticket_content.php      # Conteúdo da aba de ticket médio
├── evolucao_content.php    # Conteúdo da aba de evolução
├── manifest.json           # Metadados do addon
├── img/                    # Ícones e imagens utilizadas no relatório
│   ├── digital.png
│   ├── historico.png
│   └── icon_cliente.png
└── README.md               # Documentação do addon

```

## 🔧 Configuração

O arquivo `config.php` contém as configurações de conexão com o banco de dados. Geralmente herda as configurações do MK-AUTH automaticamente, mas pode ser personalizado se necessário:

```php
<?php
// Configurações de banco de dados
$host = 'localhost';
$user = 'seu_usuario';
$pass = 'sua_senha';
$db = 'mk_auth';

$link = mysqli_connect($host, $user, $pass, $db);
?>
```

## 📊 Estrutura do Banco de Dados

O addon utiliza as seguintes tabelas do MK-AUTH:

- **sis_caixa**: Transações financeiras (entradas e saídas)
- **sis_lanc**: Lançamentos de títulos/boletos
- **sis_cliente**: Dados dos clientes

### Campos Utilizados

**sis_caixa:**
- `entrada`: Valor de entrada
- `saida`: Valor de saída
- `data`: Data da transação
- `historico`: Descrição da transação
- `usuario`: Usuário que registrou

**sis_lanc:**
- `id`: ID do lançamento
- `login`: Login do cliente
- `datavenc`: Data de vencimento

**sis_cliente:**
- `nome`: Nome do cliente
- `login`: Login do cliente
- `uuid_cliente`: Identificador único

## 💡 Funcionalidades Técnicas

### Otimizações Implementadas
- ✅ Queries SQL otimizadas com agregações no banco
- ✅ Cache de dados entre tabelas relacionadas
- ✅ Carregamento assíncrono de gráficos
- ✅ Lazy loading de conteúdo por abas
- ✅ Índices sugeridos para melhor performance

### Recursos Visuais
- 🎨 Design moderno e responsivo
- 📱 Interface mobile-friendly
- 🌈 Gradientes e cores consistentes
- ⚡ Animações suaves
- 📊 Gráficos interativos com Chart.js 4.4.0


## 🐛 Solução de Problemas

### Gráficos não aparecem
- Verifique se o Chart.js está carregando corretamente
- Confirme que há dados no período selecionado
- Abra o console do navegador (F12) para ver erros

### Dados não aparecem
- Confirme que as tabelas do banco estão populadas
- Verifique as permissões de acesso ao addon
- Confirme a configuração de conexão em `config.php`

### Filtros não funcionam
- Limpe o cache do navegador
- Verifique os parâmetros na URL
- Confirme que as datas estão no formato correto


---