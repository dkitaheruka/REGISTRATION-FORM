<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Succès - CBCA Katoyi</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .success-card {
            text-align: center;
            padding: 50px 30px;
        }
        .success-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 25px;
            animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            filter: drop-shadow(0 10px 15px rgba(37, 99, 235, 0.2));
        }
        .success-title {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .success-message {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 35px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .back-btn {
            display: inline-flex;
            text-decoration: none;
            max-width: 250px;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); opacity: 1; }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-church logo-icon"></i>
            <h1>CBCA Katoyi</h1>
        </div>

        <div class="modern-form success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <?php 
            $type = isset($_GET['type']) ? $_GET['type'] : 'default';
            $title = "Opération réussie !";
            $message = "Vos informations ont été enregistrées avec succès dans notre base de données.";
            
            if ($type === 'update') {
                $title = "Mise à jour réussie !";
                $message = "Les informations du membre ont été actualisées avec succès.";
            } elseif ($type === 'insert') {
                $title = "Bienvenue !";
                $message = "Le nouveau membre a été enregistré avec succès dans la communauté.";
            }
            ?>

            <h2 class="success-title"><?= $title ?></h2>
            <p class="success-message"><?= $message ?></p>

            <div class="form-actions">
                <a href="index.php" class="submit-btn back-btn">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html>
