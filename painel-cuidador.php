<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] != 'cuidador') {
    header('Location: login.php');
    exit;
}

include 'conexao.php';

$user_id = $_SESSION['user_id'];
$foto_perfil = getFotoPerfil($conn, $user_id);

$cuidador_id = 0;
$especialidade = '';
$sql_cuidador = "SELECT id, especialidade FROM cuidadores WHERE usuario_id = ?";
$stmt_cuidador = $conn->prepare($sql_cuidador);
$stmt_cuidador->bind_param("i", $user_id);
$stmt_cuidador->execute();
$result_cuidador = $stmt_cuidador->get_result();
if ($result_cuidador->num_rows > 0) {
    $cuidador_data = $result_cuidador->fetch_assoc();
    $cuidador_id = $cuidador_data['id'];
    $especialidade = $cuidador_data['especialidade'] ?? 'Cuidador';
    $_SESSION['cuidador_id'] = $cuidador_id;
}

$pacientes = listarPacientes($conn, $cuidador_id);
$total_pacientes = count($pacientes['pacientes'] ?? []);
$ativos = 0;
$em_progresso = 0;
foreach ($pacientes['pacientes'] ?? [] as $p) {
    if ($p['status'] == 'ativo') $ativos++;
    if ($p['status'] == 'progresso') $em_progresso++;
}

$anotacoes = listarAnotacoes($conn, $cuidador_id);

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['adicionar_paciente'])) {
        $nome = trim($_POST['nome'] ?? '');
        $idade = trim($_POST['idade'] ?? '');
        $condicao = trim($_POST['condicao'] ?? '');
        $medicamentos = trim($_POST['medicamentos'] ?? '');
        $responsavel = trim($_POST['responsavel'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        
        if (empty($nome)) {
            $erro = 'O nome do paciente é obrigatório.';
        } else {
            $resultado = adicionarPaciente($conn, $cuidador_id, $nome, $idade, $condicao, $medicamentos, $responsavel, $telefone);
            if ($resultado['success']) {
                $sucesso = 'Paciente adicionado com sucesso!';
                $pacientes = listarPacientes($conn, $cuidador_id);
                $total_pacientes = count($pacientes['pacientes'] ?? []);
                $ativos = 0;
                $em_progresso = 0;
                foreach ($pacientes['pacientes'] ?? [] as $p) {
                    if ($p['status'] == 'ativo') $ativos++;
                    if ($p['status'] == 'progresso') $em_progresso++;
                }
            } else {
                $erro = 'Erro ao adicionar paciente: ' . $resultado['error'];
            }
        }
    }
    
    if (isset($_POST['adicionar_anotacao'])) {
        $paciente_id = intval($_POST['paciente_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'sessao';
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        
        if ($paciente_id <= 0) {
            $erro = 'Selecione um paciente.';
        } elseif (empty($descricao)) {
            $erro = 'A descrição da anotação é obrigatória.';
        } else {
            $resultado = adicionarAnotacao($conn, $cuidador_id, $paciente_id, $tipo, $titulo, $descricao);
            if ($resultado['success']) {
                $sucesso = 'Anotação salva com sucesso!';
                $anotacoes = listarAnotacoes($conn, $cuidador_id);
            } else {
                $erro = 'Erro ao salvar anotação: ' . $resultado['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Painel do Cuidador</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #0b2b40;
            --primary-light: #1a4b66;
            --secondary: #3a7ca5;
            --gray-50: #fafbfc;
            --gray-100: #f5f8fa;
            --gray-200: #e9edf0;
            --gray-600: #4a5b66;
            --gray-800: #1f2a33;
            --white: #ffffff;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.04);
            --shadow-md: 0 8px 30px rgba(0,20,30,0.08);
            --radius: 12px;
            --transition: 0.25s ease;
            --green: #27ae60;
            --red: #e74c3c;
            --orange: #f39c12;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        
        .header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(6px);
            background-color: rgba(255,255,255,0.92);
        }
        .header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .logo {
            font-size: 1.65rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo i { color: var(--secondary); font-size: 1.8rem; }
        .logo-subtitle {
            font-weight: 400;
            font-size: 0.8rem;
            color: var(--gray-600);
            background: var(--gray-200);
            padding: 2px 12px;
            border-radius: 40px;
            margin-left: 6px;
        }
        .nav-list {
            display: flex;
            gap: 28px;
            list-style: none;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-list a {
            text-decoration: none;
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .nav-list a:hover { color: var(--primary); }
        .nav-list a.active { color: var(--primary); font-weight: 600; }
        .btn-cadastro {
            background: var(--primary);
            color: white !important;
            padding: 6px 18px;
            border-radius: 40px;
        }
        .btn-cadastro:hover { background: var(--primary-light); }
        
        #painelMenu, #perfilMenu, #logoutMenu { display: none; }
        
        .perfil-link {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--gray-600);
            transition: var(--transition);
            padding: 4px 16px 4px 12px;
            border-radius: 40px;
            border: 1px solid transparent;
            background: var(--gray-100);
        }
        .perfil-link:hover {
            border-color: var(--gray-200);
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }
        .perfil-avatar { display: flex; align-items: center; gap: 8px; }
        .perfil-avatar .avatar-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }
        .perfil-avatar i { font-size: 1.8rem; color: var(--secondary); }
        .perfil-nome { font-weight: 500; font-size: 0.85rem; color: var(--gray-800); white-space: nowrap; }
        .logout-btn {
            color: var(--red) !important;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 30px;
            transition: var(--transition);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .logout-btn:hover { background: #fde8e8 !important; color: #c0392b !important; }
        
        .dashboard { padding: 32px 0 48px; }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .dashboard-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        .dashboard-header h1 i { color: var(--secondary); margin-right: 12px; }
        
        .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--white);
            padding: 8px 16px 8px 20px;
            border-radius: 60px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .user-badge i { font-size: 1.5rem; color: var(--secondary); }
        .user-badge span { font-weight: 500; }
        .user-badge .user-specialty { font-weight: 400; color: var(--gray-600); font-size: 0.8rem; }
        
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--white);
            padding: 16px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .stat-card .number { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
        .stat-card .label { font-size: 0.8rem; color: var(--gray-600); }
        
        .tabs {
            display: flex;
            gap: 4px;
            background: var(--gray-200);
            border-radius: var(--radius);
            padding: 4px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            background: transparent;
            font-weight: 500;
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            font-family: inherit;
        }
        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
        .tab-btn i { margin-right: 8px; }
        
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .patient-list { display: grid; gap: 12px; }
        .patient-item {
            background: var(--white);
            padding: 16px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            transition: var(--transition);
        }
        .patient-item:hover { border-color: var(--secondary); box-shadow: var(--shadow-sm); }
        .patient-item .name { font-weight: 600; color: var(--primary); }
        .patient-item .details { font-size: 0.85rem; color: var(--gray-600); }
        .patient-item .status {
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status.ativo { background: #d4edda; color: #155724; }
        .status.inativo { background: #f8d7da; color: #721c24; }
        .status.progresso { background: #fff3cd; color: #856404; }
        
        .anotacoes-list { display: grid; gap: 12px; }
        .anotacao-item {
            background: var(--white);
            padding: 16px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
        }
        .anotacao-item .anotacao-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }
        .anotacao-item .anotacao-titulo { font-weight: 600; color: var(--primary); }
        .anotacao-item .anotacao-paciente { font-size: 0.85rem; color: var(--gray-600); }
        .anotacao-item .anotacao-tipo {
            padding: 2px 12px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .tipo-sessao { background: #cce5ff; color: #004085; }
        .tipo-avaliacao { background: #d4edda; color: #155724; }
        .tipo-medicacao { background: #fff3cd; color: #856404; }
        .tipo-orientacao { background: #f8d7da; color: #721c24; }
        .anotacao-item .anotacao-desc { color: var(--gray-600); font-size: 0.9rem; margin-top: 8px; }
        .anotacao-item .anotacao-data { font-size: 0.75rem; color: var(--gray-600); margin-top: 8px; }
        
        .form-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .form-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 16px;
        }
        .form-card h3 i { color: var(--secondary); margin-right: 8px; }
        
        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--gray-800);
            margin-bottom: 4px;
            font-size: 0.85rem;
        }
        .form-group label .required { color: var(--red); margin-left: 2px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: inherit;
            background: var(--gray-50);
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--secondary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(58,124,165,0.1);
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-success { background: var(--green); color: white; }
        .btn-success:hover { background: #219a52; transform: translateY(-2px); }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray-600);
        }
        .empty-state i { font-size: 2rem; display: block; margin-bottom: 12px; color: var(--gray-200); }
        
        .footer {
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            padding: 28px 0;
            text-align: center;
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-top: auto;
        }
        .footer span { font-weight: 500; color: var(--primary); }
        
        @media (max-width: 768px) {
            .header .container { flex-direction: column; gap: 10px; }
            .nav-list { gap: 12px; justify-content: center; }
            .dashboard-header h1 { font-size: 1.3rem; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .tab-btn { padding: 8px 12px; font-size: 0.8rem; flex: 1; text-align: center; }
            .patient-item { flex-direction: column; align-items: flex-start; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
            .user-badge { padding: 6px 12px; }
            .user-badge span { font-size: 0.8rem; }
        }
        @media (max-width: 480px) {
            .perfil-nome { display: none; }
            .user-badge .user-specialty { display: none; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <i class="fas fa-heartbeat"></i> HomeCare
                <span class="logo-subtitle">SafeLife</span>
            </div>
            <nav>
                <ul class="nav-list">
                    <li><a href="index.php"><i class="fas fa-home"></i> Início</a></li>
                    <li><a href="servicos.php"><i class="fas fa-briefcase"></i> Serviços</a></li>
                    <li><a href="sobre.php"><i class="fas fa-users"></i> Sobre</a></li>
                    <li><a href="contato.php"><i class="fas fa-envelope"></i> Contato</a></li>
                    
                    <li id="painelMenu" style="display: none;">
                        <a href="painel-cuidador.php" id="painelLink" class="active">
                            <i class="fas fa-clipboard-list"></i> <span id="painelTexto">Painel do Cuidador</span>
                        </a>
                    </li>
                    
                    <li id="perfilMenu" style="display: none;">
                        <a href="perfil.php" class="perfil-link">
                            <div class="perfil-avatar">
                                <?php if (!empty($foto_perfil) && file_exists($foto_perfil) && is_file($foto_perfil)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto" class="avatar-img" />
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span class="perfil-nome" id="perfilNome">Olá, <?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário'); ?></span>
                            </div>
                        </a>
                    </li>
                    
                    <li id="loginMenu"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    
                    <li id="logoutMenu" style="display: none;">
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                    
                    <li id="cadastroMenu"><a href="cadastro.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1><i class="fas fa-clipboard-list"></i> Painel do Cuidador</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário'); ?></span>
                    <span class="user-specialty">• <?php echo htmlspecialchars($especialidade); ?></span>
                    <button class="logout-btn" onclick="window.location.href='logout.php'">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>

            <?php if (!empty($sucesso)): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>
            <?php if (!empty($erro)): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo $total_pacientes; ?></div>
                    <div class="label">Total de Pacientes</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $ativos; ?></div>
                    <div class="label">Pacientes Ativos</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $em_progresso; ?></div>
                    <div class="label">Em Progresso</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo count($anotacoes['anotacoes'] ?? []); ?></div>
                    <div class="label">Anotações</div>
                </div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" data-tab="pacientes"><i class="fas fa-users"></i> Meus Pacientes</button>
                <button class="tab-btn" data-tab="adicionar"><i class="fas fa-user-plus"></i> Adicionar Paciente</button>
                <button class="tab-btn" data-tab="anotacoes"><i class="fas fa-notes-medical"></i> Anotações</button>
                <button class="tab-btn" data-tab="servicos"><i class="fas fa-cogs"></i> Gerenciar Serviços</button>
            </div>

            <!-- TAB 1: PACIENTES -->
            <div id="tab-pacientes" class="tab-content active">
                <?php if ($pacientes['success'] && count($pacientes['pacientes']) > 0): ?>
                    <div class="patient-list">
                        <?php foreach ($pacientes['pacientes'] as $paciente): ?>
                            <div class="patient-item">
                                <div>
                                    <div class="name"><?php echo htmlspecialchars($paciente['nome']); ?></div>
                                    <div class="details">
                                        <?php echo htmlspecialchars($paciente['idade'] ?? 'Idade não informada'); ?> · 
                                        <?php echo htmlspecialchars($paciente['condicao'] ?? 'Sem condição'); ?>
                                        <?php if (!empty($paciente['medicamentos'])): ?>
                                            <br /><i class="fas fa-pills" style="color: var(--secondary);"></i> 
                                            <?php echo htmlspecialchars($paciente['medicamentos']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="status <?php echo $paciente['status'] ?? 'ativo'; ?>">
                                        <?php echo ucfirst($paciente['status'] ?? 'Ativo'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>Nenhum paciente cadastrado ainda.</p>
                        <p style="font-size:0.85rem;color:var(--gray-600);">Clique em "Adicionar Paciente" para começar.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: ADICIONAR PACIENTE -->
            <div id="tab-adicionar" class="tab-content">
                <div class="form-card">
                    <h3><i class="fas fa-user-plus"></i> Adicionar Novo Paciente</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome <span class="required">*</span></label>
                                <input type="text" name="nome" placeholder="Nome do paciente" required />
                            </div>
                            <div class="form-group">
                                <label>Idade</label>
                                <input type="text" name="idade" placeholder="Ex: 78 anos" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Condição de Saúde</label>
                            <input type="text" name="condicao" placeholder="Doença principal ou condição" />
                        </div>
                        <div class="form-group">
                            <label>Medicamentos em Uso</label>
                            <input type="text" name="medicamentos" placeholder="Liste os medicamentos" />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Responsável</label>
                                <input type="text" name="responsavel" placeholder="Nome do responsável" />
                            </div>
                            <div class="form-group">
                                <label>Telefone do Responsável</label>
                                <input type="tel" name="telefone" placeholder="(11) 99999-9999" />
                            </div>
                        </div>
                        <button type="submit" name="adicionar_paciente" class="btn btn-success" style="width:100%;justify-content:center;">
                            <i class="fas fa-user-plus"></i> Cadastrar Paciente
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 3: ANOTAÇÕES -->
            <div id="tab-anotacoes" class="tab-content">
                <div class="form-card" style="margin-bottom:24px;">
                    <h3><i class="fas fa-notes-medical"></i> Registrar Anotação</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Paciente <span class="required">*</span></label>
                                <select name="paciente_id" required>
                                    <option value="">Selecione um paciente</option>
                                    <?php foreach ($pacientes['pacientes'] ?? [] as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tipo</label>
                                <select name="tipo">
                                    <option value="sessao">Sessão</option>
                                    <option value="avaliacao">Avaliação</option>
                                    <option value="medicacao">Medicação</option>
                                    <option value="orientacao">Orientação</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Título</label>
                            <input type="text" name="titulo" placeholder="Título da anotação" />
                        </div>
                        <div class="form-group">
                            <label>Descrição <span class="required">*</span></label>
                            <textarea name="descricao" placeholder="Descreva a anotação..." required></textarea>
                        </div>
                        <button type="submit" name="adicionar_anotacao" class="btn btn-primary" style="width:100%;justify-content:center;">
                            <i class="fas fa-save"></i> Salvar Anotação
                        </button>
                    </form>
                </div>

                <h3 style="margin-bottom:16px;font-size:1.1rem;color:var(--primary);">
                    <i class="fas fa-list" style="color:var(--secondary);"></i> Anotações Recentes
                </h3>
                
                <?php if ($anotacoes['success'] && count($anotacoes['anotacoes']) > 0): ?>
                    <div class="anotacoes-list">
                        <?php foreach ($anotacoes['anotacoes'] as $anotacao): ?>
                            <div class="anotacao-item">
                                <div class="anotacao-header">
                                    <div>
                                        <span class="anotacao-titulo"><?php echo htmlspecialchars($anotacao['titulo'] ?? 'Sem título'); ?></span>
                                        <span class="anotacao-paciente">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($anotacao['paciente_nome'] ?? 'Paciente não encontrado'); ?>
                                        </span>
                                    </div>
                                    <span class="anotacao-tipo tipo-<?php echo $anotacao['tipo'] ?? 'sessao'; ?>">
                                        <?php echo ucfirst($anotacao['tipo'] ?? 'Sessão'); ?>
                                    </span>
                                </div>
                                <div class="anotacao-desc"><?php echo nl2br(htmlspecialchars($anotacao['descricao'])); ?></div>
                                <div class="anotacao-data">
                                    <i class="far fa-calendar-alt"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($anotacao['criado_em'] ?? $anotacao['data'] ?? 'now')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-notes-medical"></i>
                        <p>Nenhuma anotação registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 4: GERENCIAR SERVIÇOS -->
            <div id="tab-servicos" class="tab-content">
                <div class="form-card">
                    <h3><i class="fas fa-cogs"></i> Gerenciar Serviços</h3>
                    <p style="color: var(--gray-600); margin-bottom: 16px;">
                        Clique no botão abaixo para gerenciar os serviços oferecidos pela plataforma.
                    </p>
                    <a href="admin-servicos.php" class="btn btn-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-arrow-right"></i> Ir para Gerenciar Serviços
                    </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 <span>HomeCare</span> · Cuidado que transforma vidas</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
                });
            });

            const isLoggedIn = localStorage.getItem('userLoggedIn') === 'true';
            const userType = localStorage.getItem('userType');
            const userName = localStorage.getItem('userName') || 'Usuário';
            
            const painelMenu = document.getElementById('painelMenu');
            const perfilMenu = document.getElementById('perfilMenu');
            const loginMenu = document.getElementById('loginMenu');
            const logoutMenu = document.getElementById('logoutMenu');
            const cadastroMenu = document.getElementById('cadastroMenu');
            const painelLink = document.getElementById('painelLink');
            const painelTexto = document.getElementById('painelTexto');
            const perfilNome = document.getElementById('perfilNome');

            if (isLoggedIn) {
                if (painelMenu) painelMenu.style.display = 'block';
                if (perfilMenu) perfilMenu.style.display = 'block';
                if (logoutMenu) logoutMenu.style.display = 'block';
                if (loginMenu) loginMenu.style.display = 'none';
                if (cadastroMenu) cadastroMenu.style.display = 'none';
                
                if (painelLink && painelTexto) {
                    if (userType === 'cuidador') {
                        painelLink.href = 'painel-cuidador.php';
                        painelTexto.textContent = 'Painel do Cuidador';
                    } else {
                        painelLink.href = 'paciente-visualizacao.php';
                        painelTexto.textContent = 'Meu Acompanhamento';
                    }
                }
                
                if (perfilNome) {
                    let displayName = userName;
                    if (displayName.length > 18) displayName = displayName.substring(0, 18) + '...';
                    perfilNome.textContent = 'Olá, ' + displayName;
                }
            }

            document.querySelector('input[name="telefone"]')?.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                
                if (value.length > 10) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7);
                } else if (value.length > 6) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 6) + '-' + value.substring(6);
                } else if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                e.target.value = value;
            });
        });
    </script>
</body>
</html>
