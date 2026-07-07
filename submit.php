<?php
// Configuration sécurisée des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();
require_once 'config.php';

// Fonction de nettoyage des entrées
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validate_date_string($value) {
    $value = trim($value ?? '');
    if ($value === '') {
        return false;
    }
    if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
        return false;
    }
    $day = (int)$m[1];
    $month = (int)$m[2];
    $year = (int)$m[3];
    if ($month < 1 || $month > 12 || $year < 2000 || $year > 2030) {
        return false;
    }
    return checkdate($month, $day, $year);
}

function normalize_date_for_db($value) {
    $value = trim($value ?? '');
    if ($value === '') {
        return '';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        return "$m[1]-$m[2]-$m[3]";
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
        return "$m[3]-$m[2]-$m[1]";
    }
    return '';
}

function uppercase_data($data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = uppercase_data($value);
        } elseif ($value !== '' && !is_numeric($value)) {
            $data[$key] = mb_strtoupper($value, 'UTF-8');
        }
    }
    return $data;
}

// Les données sont maintenant dans la session
$data = isset($_SESSION['form_data']) ? sanitize($_SESSION['form_data']) : [];
$data = uppercase_data($data);

if (!empty($data['date_naissance']) && !validate_date_string($data['date_naissance'])) {
    $errorMessage = "Date de naissance invalide.";
    throw new Exception($errorMessage);
}
if (!empty($data['date_bapteme']) && !validate_date_string($data['date_bapteme'])) {
    $errorMessage = "Date de baptême invalide.";
    throw new Exception($errorMessage);
}
if (!empty($data['date_naissance'])) {
    $data['date_naissance'] = normalize_date_for_db($data['date_naissance']);
}
if (!empty($data['date_bapteme'])) {
    $data['date_bapteme'] = normalize_date_for_db($data['date_bapteme']);
}

if (!empty($data)) {
    try {
        // Validation basique
        if (empty($data['nom_complet']) || empty($data['date_naissance'])) {
            throw new Exception("Le nom complet et la date de naissance sont obligatoires.");
        }

        $niveauEtude = $data['niveau_etude'] ?? '';
        if ($niveauEtude === 'Autre') {
            $niveauEtude = $data['niveau_etude_autre'] ?? '';
        }
        if (empty($niveauEtude) && !empty($data['niveau_etude_autre'])) {
            $niveauEtude = $data['niveau_etude_autre'];
        }

        // 1. Vérifier si le chrétien existe déjà
        $check = $pdo->prepare("SELECT id_chretien FROM chretiens WHERE nom_complet = ? AND date_naissance = ?");
        $check->execute([$data['nom_complet'], $data['date_naissance']]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 2. S'il existe → UPDATE
            $sql = "UPDATE chretiens SET
                baptise = ?, lieu_naissance = ?, sexe = ?, lieu_bapteme = ?, date_bapteme = ?,
                communiant = ?, id_service = ?, id_ministere = ?, adresse = ?, id_kijiji = ?,
                id_cellule = ?, id_profession = ?, niveau_etude = ?, handicape = ?, vulnerable = ?,
                statut_civil = ?, id_lieu = ?, telephone = ?
                WHERE id_chretien = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['baptise'] ?? 0,
                $data['lieu_naissance'],
                $data['sexe'] ?? 'M',
                $data['lieu_bapteme'] ?? null,
                !empty($data['date_bapteme']) ? $data['date_bapteme'] : null,
                $data['communiant'] ?? 0,
                !empty($data['id_service']) ? $data['id_service'] : null,
                !empty($data['id_ministere']) ? $data['id_ministere'] : null,
                $data['adresse'] ?? '',
                !empty($data['id_kijiji']) ? $data['id_kijiji'] : null,
                !empty($data['id_cellule']) ? $data['id_cellule'] : null,
                !empty($data['id_profession']) ? $data['id_profession'] : null,
                $niveauEtude,
                $data['handicape'] ?? 'Aucun',
                $data['vulnerable'] ?? 'Aucune',
                $data['statut_civil'] ?? 'Célibataire',
                !empty($data['id_lieu']) ? $data['id_lieu'] : null,
                $data['telephone'] ?? '',
                $existing['id_chretien']
            ]);

            // Vider la session après succès
            unset($_SESSION['form_data']);
            header("Location: success.php?type=update");
            exit;

        } else {
            // 3. Sinon → INSERT
            $sql = "INSERT INTO chretiens (
                nom_complet, baptise, lieu_naissance, date_naissance, sexe,
                lieu_bapteme, date_bapteme, communiant, id_service, id_ministere,
                adresse, id_kijiji, id_cellule, id_profession, niveau_etude, handicape,
                vulnerable, statut_civil, id_lieu, telephone
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom_complet'],
                $data['baptise'] ?? 0,
                $data['lieu_naissance'],
                $data['date_naissance'],
                $data['sexe'] ?? 'M',
                $data['lieu_bapteme'] ?? null,
                !empty($data['date_bapteme']) ? $data['date_bapteme'] : null,
                $data['communiant'] ?? 0,
                !empty($data['id_service']) ? $data['id_service'] : null,
                !empty($data['id_ministere']) ? $data['id_ministere'] : null,
                $data['adresse'] ?? '',
                !empty($data['id_kijiji']) ? $data['id_kijiji'] : null,
                !empty($data['id_cellule']) ? $data['id_cellule'] : null,
                !empty($data['id_profession']) ? $data['id_profession'] : null,
                $niveauEtude,
                $data['handicape'] ?? 'Aucun',
                $data['vulnerable'] ?? 'Aucune',
                $data['statut_civil'] ?? 'Célibataire',
                !empty($data['id_lieu']) ? $data['id_lieu'] : null,
                $data['telephone'] ?? ''
            ]);

            // Vider la session après succès
            unset($_SESSION['form_data']);
            header("Location: success.php?type=insert");
            exit;
        }

    } catch (Exception $e) {
        // Log l'erreur réelle pour le développeur (dans un fichier log idéalement)
        error_log("Erreur d'inscription : " . $e->getMessage());
        $errorMessage = "Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer plus tard.";
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - CBCA Katoyi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="result-page">
    <div class="container">
        <div class="header">
            <i class="fas fa-church logo-icon"></i>
            <h1>CBCA Katoyi</h1>
        </div>
        <div class="modern-form">
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> 
                Erreur : <?= htmlspecialchars($errorMessage) ?>
            </div>
            <div class="form-actions">
                <a href="index.php" class="submit-btn" style="background: var(--secondary-color)">
                    <i class="fas fa-redo"></i> Réessayer
                </a>
            </div>
        </div>
    </div>
</body>
</html>
