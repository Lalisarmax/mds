<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'conexao.php';

$user_id = $_SESSION['user_id'];
$foto_perfil = getFotoPerfil($conn, $user_id);
$user_nome = $_SESSION['user_nome'] ?? 'Usuário';

$dados_saude = [];
$sql_saude = "SELECT * FROM usuarios WHERE id = ?";
$stmt_saude = $conn->prepare($sql_saude);
$stmt_saude->bind_param("i", $user_id);
$stmt_saude->execute();
$result_saude = $stmt_saude->get_result();
if ($result_saude->num_rows > 0) {
    $dados_saude = $result_saude->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Meu Acompanhamento</title>
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
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 30px rgba(0,20,30,0.08);
            --shadow-lg: 0 20px 60px rgba(0,20,30,0.12);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-200);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo i { color: var(--secondary); font-size: 1.6rem; }
        .logo-subtitle {
            font-weight: 400;
            font-size: 0.7rem;
            color: var(--gray-600);
            background: var(--gray-200);
            padding: 2px 12px;
            border-radius: 20px;
            margin-left: 4px;
        }
        .nav-list {
            display: flex;
            gap: 24px;
            list-style: none;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-list a {
            text-decoration: none;
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
            padding: 6px 12px;
            border-radius: 30px;
        }
        .nav-list a:hover { color: var(--primary); background: var(--gray-100); }
        .nav-list a.active { color: var(--primary); background: var(--gray-100); font-weight: 600; }
        .btn-cadastro {
            background: var(--primary);
            color: white !important;
            padding: 6px 18px !important;
            border-radius: 30px !important;
        }
        .btn-cadastro:hover { background: var(--primary-light) !important; }
        
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
            background: var(--gray-50);
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--white);
            padding: 20px 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .stat-card .number { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
        .stat-card .label { font-size: 0.8rem; color: var(--gray-600); }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .info-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .info-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-card h3 i { color: var(--secondary); }
        .info-card .item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .info-card .item:last-child { border-bottom: none; }
        .info-card .item .label { color: var(--gray-600); font-size: 0.85rem; }
        .info-card .item .value { font-weight: 500; color: var(--gray-800); }
        
        .btn-profile {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }
        .btn-profile:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
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
            .info-grid { grid-template-columns: 1fr; }
            .perfil-nome { font-size: 0.75rem; }
            .perfil-avatar .avatar-img { width: 28px; height: 28px; }
        }
        @media (max-width: 480px) {
            .perfil-nome { display: none; }
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
                        <a href="paciente-visualizacao.php" id="painelLink" class="active">
                            <i class="fas fa-clipboard-list"></i> <span id="painelTexto">Meu Acompanhamento</span>
                        </a>
                    </li>
                    
                    <li id="loginMenu"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li id="cadastroMenu"><a href="cadastro.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                    
                    <li id="perfilMenu" style="display: none;">
                        <a href="perfil.php" class="perfil-link">
                            <div class="perfil-avatar">
                                <?php if (!empty($foto_perfil) && file_exists($foto_perfil) && is_file($foto_perfil)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto" class="avatar-img" />
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span class="perfil-nome" id="perfilNome">Olá, <?php echo htmlspecialchars($user_nome); ?></span>
                            </div>
                        </a>
                    </li>
                    
                    <li id="logoutMenu" style="display: none;">
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1><i class="fas fa-heart"></i> Meu Acompanhamento</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($user_nome); ?></span>
                    <a href="perfil.php" class="btn-profile" style="padding:6px 16px;font-size:0.8rem;">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><i class="fas fa-check-circle" style="color:var(--green);font-size:1.5rem;"></i></div>
                    <div class="label">Status: Ativo</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $dados_saude['data_nascimento'] ? date_diff(date_create($dados_saude['data_nascimento']), date_create('now'))->y . '+' : 'N/A'; ?></div>
                    <div class="label">Idade</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo !empty($dados_saude['plano_saude']) ? '✓' : '—'; ?></div>
                    <div class="label">Plano de Saúde</div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <h3><i class="fas fa-notes-medical"></i> Informações de Saúde</h3>
                    <div class="item">
                        <span class="label">Condições de Saúde</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['condicao_saude'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Medicamentos</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['medicamentos'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Plano de Saúde</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['plano_saude'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Contato Familiar</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['contato_familiar'] ?? 'Não informado'); ?></span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-id-card"></i> Dados Pessoais</h3>
                    <div class="item">
                        <span class="label">Nome</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['nome'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">E-mail</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['email'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Telefone</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['telefone'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">CPF</span>
                        <span class="value"><?php echo htmlspecialchars($dados_saude['cpf'] ?? 'Não informado'); ?></span>
                    </div>
                    <div class="item" style="margin-top:8px;border-top:2px solid var(--gray-200);padding-top:12px;">
                        <a href="perfil.php" class="btn-profile" style="width:100%;justify-content:center;">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </a>
                    </div>
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
        });
    </script>
</body>
</html>
