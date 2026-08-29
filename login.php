<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (isset($_SESSION['user_id'])) {
    if<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_tipo'] == 'cuidador') {
        header('Location: painel-cuidador.php');
    } else {
        header('Location: paciente-visualizacao.php');
    }
    exit;
}

include 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            $senha_valida = false;
            if (password_get_info($user['senha'])['algo']) {
                $senha_valida = password_verify($senha, $user['senha']);
            } else {
                $senha_valida = ($user['senha'] == md5($senha));
            }
            
            if ($senha_valida) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_tipo'] = $user['tipo'];
                $_SESSION['foto_perfil'] = $user['foto_perfil'] ?? '';
                
                if ($user['tipo'] == 'cuidador') {
                    $sql2 = "SELECT id FROM cuidadores WHERE usuario_id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($row = $result2->fetch_assoc()) {
                        $_SESSION['cuidador_id'] = $row['id'];
                    }
                }
                
                echo "<script>
                    localStorage.setItem('userLoggedIn', 'true');
                    localStorage.setItem('userType', '" . addslashes($user['tipo']) . "');
                    localStorage.setItem('userName', '" . addslashes($user['nome']) . "');
                    localStorage.setItem('userId', '" . $user['id'] . "');
                </script>";
                
                if ($user['tipo'] == 'cuidador') {
                    header('Location: painel-cuidador.php');
                } else {
                    header('Location: paciente-visualizacao.php');
                }
                exit;
            } else {
                $erro = 'Senha incorreta.';
            }
        } else {
            $erro = 'Usuário não encontrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeCare · Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f5f8fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 48px 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,20,30,0.12);
            max-width: 420px;
            width: 100%;
            border: 1px solid #e9edf0;
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0b2b40, #3a7ca5);
        }
        .login-container .logo {
            text-align: center;
            font-size: 2.5rem;
            color: #3a7ca5;
            margin-bottom: 8px;
        }
        .login-container h2 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #0b2b40;
            margin-bottom: 4px;
        }
        .login-container .subtitle {
            text-align: center;
            color: #4a5b66;
            margin-bottom: 28px;
            font-size: 0.9rem;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1f2a33;
            margin-bottom: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9edf0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: 0.3s ease;
            background: #fafbfc;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3a7ca5;
            background: white;
            box-shadow: 0 0 0 4px rgba(58,124,165,0.1);
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 48px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #4a5b66;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px;
        }
        .password-toggle:hover { color: #0b2b40; }
        .btn-login {
            background: #0b2b40;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
            font-family: inherit;
        }
        .btn-login:hover {
            background: #1a4b66;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(11,43,64,0.2);
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #4a5b66;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #3a7ca5;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover { text-decoration: underline; }
        .test-credentials {
            margin-top: 20px;
            padding: 16px;
            background: #f5f8fa;
            border-radius: 10px;
            border: 1px dashed #e9edf0;
            font-size: 0.8rem;
            color: #4a5b66;
        }
        .test-credentials strong { color: #0b2b40; }
        .test-credentials .row { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 8px; }
        .test-credentials .col { flex: 1; min-width: 150px; }
        .test-credentials code { 
            background: white; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-size: 0.75rem;
            border: 1px solid #e9edf0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo"><i class="fas fa-heartbeat"></i></div>
        <h2>Bem-vindo</h2>
        <p class="subtitle">Faça login para acessar sua conta</p>

        <?php if (!empty($erro)): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" placeholder="seu@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
            </div>
            <div class="form-group">
                <label>Senha</label>
                <div class="password-wrapper">
                    <input type="password" name="senha" id="senha" placeholder="Digite sua senha" required />
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="register-link">
            Não tem uma conta? <a href="cadastro.php">Cadastre-se</a>
        </div>

        <div class="test-credentials">
            <strong>🧪 Contas de teste:</strong>
            <div class="row">
                <div class="col">
                    <strong>Paciente:</strong><br>
                    <code>paciente@homecare.com</code><br>
                    Senha: <code>1234teste</code>
                </div>
                <div class="col">
                    <strong>Cuidador:</strong><br>
                    <code>cuidador@homecare.com</code><br>
                    Senha: <code>1234teste</code>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senha = document.getElementById('senha');
            const eye = document.getElementById('eyeIcon');
            if (senha.type === 'password') {
                senha.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                senha.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>


$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            $senha_valida = false;
            if (password_get_info($user['senha'])['algo']) {
                $senha_valida = password_verify($senha, $user['senha']);
            } else {
                $senha_valida = ($user['senha'] == md5($senha));
            }
            
            if ($senha_valida) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_tipo'] = $user['tipo'];
                $_SESSION['foto_perfil'] = $user['foto_perfil'] ?? '';
                
                if ($user['tipo'] == 'cuidador') {
                    $sql2 = "SELECT id FROM cuidadores WHERE usuario_id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($row = $result2->fetch_assoc()) {
                        $_SESSION['cuidador_id'] = $row['id'];
                    }
                }
                
                echo "<script>
                    localStorage.setItem('userLoggedIn', 'true');
                    localStorage.setItem('userType', '" . addslashes($user['tipo']) . "');
                    localStorage.setItem('userName', '" . addslashes($user['nome']) . "');
                    localStorage.setItem('userId', '" . $user['id'] . "');
                </script>";
                
                if ($user['tipo'] == 'cuidador') {
                    header('Location: painel-cuidador.php');
                } else {
                    header('Location: paciente-visualizacao.php');
                }
                exit;
            } else {
                $erro = 'Senha incorreta.';
            }
        } else {
            $erro = 'Usuário não encontrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeCare · Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f5f8fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 48px 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,20,30,0.12);
            max-width: 420px;
            width: 100%;
            border: 1px solid #e9edf0;
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0b2b40, #3a7ca5);
        }
        .login-container .logo {
            text-align: center;
            font-size: 2.5rem;
            color: #3a7ca5;
            margin-bottom: 8px;
        }
        .login-container h2 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #0b2b40;
            margin-bottom: 4px;
        }
        .login-container .subtitle {
            text-align: center;
            color: #4a5b66;
            margin-bottom: 28px;
            font-size: 0.9rem;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1f2a33;
            margin-bottom: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9edf0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: 0.3s ease;
            background: #fafbfc;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3a7ca5;
            background: white;
            box-shadow: 0 0 0 4px rgba(58,124,165,0.1);
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 48px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #4a5b66;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px;
        }
        .password-toggle:hover { color: #0b2b40; }
        .btn-login {
            background: #0b2b40;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
            font-family: inherit;
        }
        .btn-login:hover {
            background: #1a4b66;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(11,43,64,0.2);
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #4a5b66;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #3a7ca5;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover { text-decoration: underline; }
        .test-credentials {
            margin-top: 20px;
            padding: 16px;
            background: #f5f8fa;
            border-radius: 10px;
            border: 1px dashed #e9edf0;
            font-size: 0.8rem;
            color: #4a5b66;
        }
        .test-credentials strong { color: #0b2b40; }
        .test-credentials .row { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 8px; }
        .test-credentials .col { flex: 1; min-width: 150px; }
        .test-credentials code { 
            background: white; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-size: 0.75rem;
            border: 1px solid #e9edf0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo"><i class="fas fa-heartbeat"></i></div>
        <h2>Bem-vindo</h2>
        <p class="subtitle">Faça login para acessar sua conta</p>

        <?php if (!empty($erro)): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" placeholder="seu@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
            </div>
            <div class="form-group">
                <label>Senha</label>
                <div class="password-wrapper">
                    <input type="password" name="senha" id="senha" placeholder="Digite sua senha" required />
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="register-link">
            Não tem uma conta? <a href="cadastro.php">Cadastre-se</a>
        </div>

        <div class="test-credentials">
            <strong>🧪 Contas de teste:</strong>
            <div class="row">
                <div class="col">
                    <strong>Paciente:</strong><br>
                    <code>paciente@homecare.com</code><br>
                    Senha: <code>1234teste</code>
                </div>
                <div class="col">
                    <strong>Cuidador:</strong><br>
                    <code>cuidador@homecare.com</code><br>
                    Senha: <code>1234teste</code>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senha = document.getElementById('senha');
            const eye = document.getElementById('eyeIcon');
            if (senha.type === 'password') {
                senha.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                senha.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
