<?php
session_start();
include 'db.php';

// Processa a busca se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['especialidade'])) {
    header("Location: medicoEspecialista.php?especialidade=" . urlencode($_POST['especialidade']));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agende Já</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .login-select {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.2rem;
            padding-top: 0.5rem;
        }

        .item-login {
            background: transparent;
            border: none;
            color: #0987d9;
        }

        .navbar-custom {
            background: linear-gradient(90deg, #11dadc, #0987d9);
            padding: 20px 0;
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white !important;
            font-size: 1.2rem;
        }

        .navbar-custom .nav-link:hover {
            color: #f8f9fa !important;
        }

        .hero-section {
            background: linear-gradient(90deg, #11dadc, #0987d9), url('https://images.unsplash.com/photo-1631815588090-d1bcbe9b4b59?q=80&w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            padding: 100px 0 120px;
            position: relative;
        }

        .search-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            top: 40px;
        }

        .search-box {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }

        .search-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .search-input-container {
            position: relative;
        }

        .search-input {
            padding-left: 45px;
            height: 55px;
            font-size: 1.1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #4299e1;
            font-size: 1.2rem;
        }

        .search-button {
            height: 55px;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 0 30px;
            background-color: #4299e1;
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background-color: #3182ce;
            transform: translateY(-2px);
        }

        .featured-section {
            padding: 30px 0;
            /* Reduzi o padding para diminuir a distância */
        }

        .card {
            transition: transform 0.3s;
        }

        .card:hover {
            transform: scale(1.05);
        }

        .about-section {
            background-color: #f8f9fa;
            padding: 50px 0;
            text-align: center;
        }

        .footer {
            background-color: #4299e1;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">

            <a class="navbar-brand" href="Home.php">
                <img src="imagens/logo.png" alt="" height="50" class="d-inline-block align-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="pagAgendamento.php">Agendar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="meusDados.php">Meus Dados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="chat_list.php">Chats</a>
                    </li>
                    <li class="nav-item">
                        <select class="login-select form-select" onchange="window.location.href=this.value" Login>
                            <option class="item-login" value="">Login</option>
                            <option class="item-login" value="loginMedico.php">Médico</option>
                            <option class="item-login" value="loginPaciente.php">Paciente</option>
                        </select>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">Cuidando da sua saúde com facilidade</h1>
                    <p class="lead mb-5"><b>Encontre os melhores médicos e agende consultas online de forma rápida e
                            segura.</p></b>
                </div>
            </div>

            <div class="search-container">
                <div class="search-box">
                    <h3 class="search-title">Encontre a especialidade médica que você precisa</h3>
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-9">
                                <div class="search-input-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="form-control search-input" name="especialidade"
                                        placeholder="Buscar por especialidade" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary search-button w-100">Buscar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Section -->
    <section class="featured-section">
        <div class="container">
            <h2 class="text-center mb-5">Destaques</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <img src="imagens/cardiologista.jpeg" class="card-img-top" alt="Cardiologia">
                        <div class="card-body">
                            <h5 class="card-title">Cardiologia</h5>
                            <p class="card-text">Encontre os melhores cardiologistas perto de você.</p>
                            <a href="medicoEspecialista.php?especialidade=Cardiologia" class="btn btn-primary">Ver mais
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <img src="imagens/dermatologia.png" class="card-img-top" alt="Dermatologia">
                        <div class="card-body">
                            <h5 class="card-title">Dermatologia</h5>
                            <p class="card-text">Cuide da sua pele com os melhores dermatologistas.</p>
                            <a href="medicoEspecialista.php?especialidade=Dermatologia" class="btn btn-primary">Ver mais
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <img src="imagens/pediatra.jpg" class="card-img-top" alt="Pediatria">
                        <div class="card-body">
                            <h5 class="card-title">Pediatria</h5>
                            <p class="card-text">Cuidados pediátricos com profissionais experientes.</p>
                            <a href="medicoEspecialista.php?especialidade=Pediatria" class="btn btn-primary">Ver mais
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section">
        <div class="container">
            <h2 class="text-center mb-4">Sobre Nós</h2>
            <p class="lead">
                O <strong>Agende Já</strong> é uma plataforma dedicada a conectar pacientes aos melhores profissionais
                de saúde.
                Nosso objetivo é facilitar o agendamento de consultas, proporcionando uma experiência simples, rápida e
                segura.
                Acreditamos que a saúde é um direito de todos, e estamos aqui para ajudar você a cuidar do que mais
                importa.
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Agende Já. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>