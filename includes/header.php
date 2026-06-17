<?php
// Démarrage de la session
session_start();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <!-- Responsive mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>BookPro</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/assets/style.css">
</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">

    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="/index.php">
            BookPro
        </a>

        <!-- Bouton mobile -->
        <button 
            class="navbar-toggler" 
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="menu">

            <ul class="navbar-nav ms-auto">

                <!-- Accueil -->
                <li class="nav-item">
                    <a class="nav-link" href="/index.php">
                        Accueil
                    </a>
                </li>

                <!-- Métiers -->
                

                    <ul class="dropdown-menu">


                        <li>
                            <a class="dropdown-item" href="#">
                                Plombier
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="#">
                                Mécanicien
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="#">
                                Menuisier
                            </a>
                        </li>

                    </ul>
                </li>

                <!-- Si connecté -->
                <?php if(isset($_SESSION['role'])): ?>

                    <!-- Admin -->
                    <?php if($_SESSION['role'] == 'admin'): ?>

                        <li class="nav-item">
                            <a class="nav-link" href="/admin/dashboard.php">
                                Dashboard Admin
                            </a>
                        </li>

                    <!-- Client -->
                    <?php else: ?>

                        <li class="nav-item">
                            <a class="nav-link" href="/client/dashboard.php">
                                Mon espace
                            </a>
                        </li>

                    <?php endif; ?>

                    <!-- Déconnexion -->
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="/auth/logout.php">
                            Déconnexion
                        </a>
                    </li>

                <!-- Si non connecté -->
                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="/auth/login.php">
                            Se Connecter
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>