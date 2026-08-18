<?php
session_start();
include 'conexao.php';

$foto_perfil = '';
if (isset($_SESSION['user_id'])) {
    $foto_perfil = getFotoPerfil($conn, $_SESSION['user_id']);
}

$servicos = [];
$sql_servicos = "SELECT * FROM servicos WHERE ativo = 1 ORDER BY id";
$result_servicos = $conn->query($sql_servicos);
if ($result_servicos) {
    while ($row = $result_servicos->fetch_assoc()) {
        $servicos[] = $row;
    }
}

if (empty($servicos)) {
    $servicos = [
        [
            'nome' => 'Cuidados Domiciliares',
            'descricao' => 'Assistência completa no conforto do seu lar, com profissionais qualificados e humanizados.',
            'icone' => 'fa-home',
            'beneficios' => 'Conforto, segurança, atendimento personalizado',
            'equipe' => 'Enfermeiros, técnicos de enfermagem, cuidadores'
        ],
        [
            'nome' => 'Acompanhamento de Idosos',
            'descricao' => 'Suporte personalizado para idosos, garantindo qualidade de vida e bem-estar diário.',
            'icone' => 'fa-heart',
            'beneficios' => 'Qualidade de vida, socialização, saúde mental',
            'equipe' => 'Cuidadores especializados, psicólogos'
        ],
        [
            'nome' => 'Enfermagem Especializada',
            'descricao' => 'Cuidados de enfermagem com expertise em geriatria e cuidados paliativos.',
            'icone' => 'fa-user-md',
            'beneficios' => 'Cuidados técnicos, medicação, monitoramento',
            'equipe' => 'Enfermeiros especializados'
        ],
        [
            'nome' => 'Fisioterapia Domiciliar',
            'descricao' => 'Reabilitação e exercícios terapêuticos no conforto da sua casa.',
            'icone' => 'fa-bone',
            'beneficios' => 'Recuperação motora, qualidade de vida',
            'equipe' => 'Fisioterapeutas'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HomeCare · Serviços</title>
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
            --red: #e74c3c;
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 60px 0 40px;
        }
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .page-header p {
            opacity: 0.9;
            max-width: 600px;
        }
        
        .section { padding: 60px 0; }
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .section-title h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        .section-title p {
            color: var(--gray-600);
            max-width: 600px;
            margin: 8px auto 0;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        .service-card {
            background: var(--white);
            padding: 32px 24px;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary);
        }
        .service-card .icon {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 12px;
            display: block;
        }
        .service-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .service-card .desc {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 12px;
        }
        .service-card .beneficios {
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        .service-card .beneficios strong { color: var(--primary); }
        .service-card .equipe {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-top: 8px;
        }
        .service-card .equipe strong { color: var(--primary); }
        .service-card .saiba-mais {
            display: inline-block;
            margin-top: 12px;
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.85rem;
        }
        .service-card .saiba-mais i { margin-left: 4px; }
        
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
            .page-header h1 { font-size: 1.8rem; }
            .services-grid { grid-template-columns: 1fr; }
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
                    <li><a href="servicos.php" class="active"><i class="fas fa-briefcase"></i> Serviços</a></li>
                    <li><a href="sobre.php"><i class="fas fa-users"></i> Sobre</a></li>
                    <li><a href="contato.php"><i class="fas fa-envelope"></i> Contato</a></li>
                    
                    <li id="painelMenu" style="display: none;">
                        <a href="painel-cuidador.php" id="painelLink">
                            <i class="fas fa-clipboard-list"></i> <span id="painelTexto">Painel</span>
                        </a>
                    </li>
                    
                    <li id="loginMenu"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li id="cadastroMenu"><a href="cadastro.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                    
                    <li id="perfilMenu" style="display: none;">
                        <a href="perfil.php" class="perfil-link">
                            <div class="perfil-avatar">
                                <?php if (!empty($foto_perfil) && file_exists($foto_perfil)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto" class="avatar-img" />
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span class="perfil-nome" id="perfilNome">Olá, Usuário</span>
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

    <section class="page-header">
        <div class="container">
            <h1>Nossos Serviços</h1>
            <p>Conheça os serviços que oferecemos para cuidar de você e de quem você ama.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="services-grid">
                <?php foreach ($servicos as $servico): ?>
                    <a href="servico-detalhe.php?id=<?php echo $servico['id'] ?? 1; ?>" class="service-card">
                        <span class="icon"><i class="fas <?php echo $servico['icone'] ?? 'fa-heart'; ?>"></i></span>
                        <h3><?php echo htmlspecialchars($servico['nome']); ?></h3>
                        <p class="desc"><?php echo htmlspecialchars($servico['descricao']); ?></p>
                        <?php if (!empty($servico['beneficios'])): ?>
                            <div class="beneficios">
                                <strong>Benefícios:</strong> <?php echo htmlspecialchars($servico['beneficios']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($servico['equipe'])): ?>
                            <div class="equipe">
                                <strong>Equipe:</strong> <?php echo htmlspecialchars($servico['equipe']); ?>
                            </div>
                        <?php endif; ?>
                        <span class="saiba-mais">Saiba mais <i class="fas fa-arrow-right"></i></span>
                    </a>
                <?php endforeach; ?>
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
