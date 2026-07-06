<?php
// Configuration sécurisée des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();
require_once 'functions.php';

// Régénérer l'ID de session périodiquement pour éviter la fixation de session
if (!isset($_SESSION['last_regeneration'])) {
  session_regenerate_id(true);
  $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) {
  session_regenerate_id(true);
  $_SESSION['last_regeneration'] = time();
}

// Initialiser les données de session si elles n'existent pas
if (!isset($_SESSION['form_data'])) {
  $_SESSION['form_data'] = [];
}

// Déterminer l'étape actuelle
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1) $step = 1;
if ($step > 4) $step = 4;

// Traiter les données POST de l'étape précédente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Vérification CSRF
  if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    die("Erreur de sécurité : Jeton CSRF invalide.");
  }

  $prev_step = isset($_POST['prev_step']) ? (int)$_POST['prev_step'] : 0;

  // Fusionner les données POST dans la session
  foreach ($_POST as $key => $value) {
    if ($key !== 'prev_step' && $key !== 'next_step' && $key !== 'csrf_token') {
      $_SESSION['form_data'][$key] = $value;
    }
  }

  // Redirection vers l'étape suivante ou submit.php
  if (isset($_POST['next_step'])) {
    $next = (int)$_POST['next_step'];
    if ($next > 4) {
      header("Location: submit.php");
      exit;
    } else {
      header("Location: index.php?step=" . $next);
      exit;
    }
  }
}

// Récupérer les options pour les selects
$services     = getOptions('services', 'id_service', 'nom_service');
$ministeres   = getOptions('ministeres', 'id_ministere', 'nom_ministere');
$kijijis      = getOptions('kijiji', 'id_kijiji', 'nom_kijiji');
$cellules     = getOptions('cellule', 'id_cellule', 'nom_cellule');
$professions  = getOptions('professions', 'id_profession', 'nom_profession');
$lieux        = getOptions('lieux_culte', 'id_lieu', 'nom_lieu');

// Helper pour récupérer une valeur de session
function val($key, $default = '')
{
  return isset($_SESSION['form_data'][$key]) ? htmlspecialchars($_SESSION['form_data'][$key]) : $default;
}

// Helper pour tester si une option est sélectionnée
function selected($key, $value)
{
  return (isset($_SESSION['form_data'][$key]) && $_SESSION['form_data'][$key] == $value) ? 'selected' : '';
}

$niveauEtudeOptions = [
  'Aucun',
  'Primaire',
  'Secondaire',
  'Bac 1',
  'Bac 2',
  'Bac 3',
  'Bac 4',
  'Bac 5',
  'Graduat',
  'Licence (L1)',
  'Licence (L2)',
  'Licence (L3)',
  'Master (M1)',
  'Master (M2)',
  'Doctorat (PhD)',
  'Autre'
];

if (!empty($_SESSION['form_data']['niveau_etude']) && !in_array($_SESSION['form_data']['niveau_etude'], $niveauEtudeOptions, true)) {
  if (empty($_SESSION['form_data']['niveau_etude_autre'])) {
    $_SESSION['form_data']['niveau_etude_autre'] = $_SESSION['form_data']['niveau_etude'];
  }
  $_SESSION['form_data']['niveau_etude'] = 'Autre';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CBCA Katoyi - Étape <?= $step ?> / 4</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <div class="container">
    <div class="header">
      <i class="fas fa-church logo-icon"></i>
      <h1>CBCA Katoyi</h1>
      <p>Enregistrement des membres - Page <?= $step ?> sur 4</p>
    </div>

    <div class="modern-form">
      <!-- Barre de progression -->
      <div class="progress-container">
        <div class="progress-bar-bg"></div>
        <div class="progress-bar-fill" style="width: <?= (($step - 1) / 3) * 100 ?>%;"></div>
        <div class="progress-steps">
          <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
            <?= $step > 1 ? '<i class="fas fa-check"></i>' : '<i class="fas fa-user"></i>' ?>
          </div>
          <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
            <?= $step > 2 ? '<i class="fas fa-check"></i>' : '<i class="fas fa-dove"></i>' ?>
          </div>
          <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">
            <?= $step > 3 ? '<i class="fas fa-check"></i>' : '<i class="fas fa-hand-holding-heart"></i>' ?>
          </div>
          <div class="step <?= $step >= 4 ? 'active' : '' ?>">
            <i class="fas fa-briefcase"></i>
          </div>
        </div>
      </div>

      <form method="POST" action="index.php?step=<?= $step ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="prev_step" value="<?= $step ?>">

        <?php if ($step === 1): ?>
          <!-- Étape 1: Informations Personnelles -->
          <div class="form-step">
            <div class="form-section">
              <h3><i class="fas fa-user"></i> Informations Personnelles</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label>Nom complet</label>
                  <input type="text" name="nom_complet" value="<?= val('nom_complet') ?>" placeholder="Ex: KITAHERUKA NGWALI DAVID" required>
                </div>
                <div class="form-group">
                  <label>Sexe</label>
                  <select name="sexe" required>
                    <option value="M" <?= selected('sexe', 'M') ?>>Masculin</option>
                    <option value="F" <?= selected('sexe', 'F') ?>>Féminin</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Date de naissance</label>
                  <input type="date" name="date_naissance" value="<?= val('date_naissance') ?>" required>
                </div>
                <div class="form-group">
                  <label>Lieu de naissance</label>
                  <input type="text" name="lieu_naissance" value="<?= val('lieu_naissance') ?>" placeholder="Ville ou Territoire" required>
                </div>
                <div class="form-group">
                  <label>État civil</label>
                  <select name="statut_civil" required>
                    <option value="Célibataire" <?= selected('statut_civil', 'Célibataire') ?>>Célibataire</option>
                    <option value="Marié(e)" <?= selected('statut_civil', 'Marié(e)') ?>>Marié(e)</option>
                    <option value="Divorcé(e)" <?= selected('statut_civil', 'Divorcé(e)') ?>>Divorcé(e)</option>
                    <option value="Veuf/Veuve" <?= selected('statut_civil', 'Veuf/Veuve') ?>>Veuf/Veuve</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>N° Téléphone</label>
                  <input type="text" name="telephone" value="<?= val('telephone') ?>" placeholder="+243 ...">
                </div>
              </div>
            </div>
            <div class="step-buttons">
              <button type="submit" name="next_step" value="2" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
            </div>
          </div>

        <?php elseif ($step === 2): ?>
          <!-- Étape 2: Baptême & Vie Chrétienne -->
          <div class="form-step">
            <div class="form-section">
              <h3><i class="fas fa-dove"></i> Baptême & Vie Chrétienne</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label>Baptisé ?</label>
                  <select name="baptise" id="baptise" onchange="toggleBaptemeFields()" required>
                    <option value="1" <?= selected('baptise', '1') ?>>Oui</option>
                    <option value="0" <?= selected('baptise', '0') ?>>Non</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Paroisse de baptême</label>
                  <input type="text" name="lieu_bapteme" id="paroisse_bapteme" value="<?= val('lieu_bapteme') ?>" placeholder="Nom de l'église" required>
                </div>
                <div class="form-group">
                  <label>Date de baptême</label>
                  <input type="date" name="date_bapteme" id="date_bapteme" value="<?= val('date_bapteme') ?>" required>
                </div>
                <div class="form-group">
                  <label>Membre communiant ?</label>
                  <select name="communiant" id="communion" required>
                    <option value="1" <?= selected('communiant', '1') ?>>Oui</option>
                    <option value="0" <?= selected('communiant', '0') ?>>Non</option>
                  </select>
                </div>
                <div class="form-group full-width">
                  <label>Lieu du culte habituel</label>
                  <select name="id_lieu" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($lieux as $l): ?>
                      <option value="<?= $l['id_lieu'] ?>"
                        <?= selected('id_lieu', $l['id_lieu']) ?: (empty(val('id_lieu')) && $l['id_lieu'] == 1 ? 'selected' : '') ?>>
                        <?= htmlspecialchars($l['nom_lieu']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="step-buttons">
              <a href="index.php?step=1" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</a>
              <button type="submit" name="next_step" value="3" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
            </div>
          </div>

        <?php elseif ($step === 3): ?>
          <!-- Étape 3: Engagement & Localisation -->
          <div class="form-step">
            <div class="form-section">
              <h3><i class="fas fa-hand-holding-heart"></i> Engagement & Localisation</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label>Service</label>
                  <select name="id_service" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($services as $s): ?>
                      <option value="<?= $s['id_service'] ?>" <?= selected('id_service', $s['id_service']) ?>><?= $s['nom_service'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Ministère</label>
                  <select name="id_ministere" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($ministeres as $m): ?>
                      <option value="<?= $m['id_ministere'] ?>" <?= selected('id_ministere', $m['id_ministere']) ?>><?= $m['nom_ministere'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group full-width">
                  <label>Adresse actuelle</label>
                  <input type="text" name="adresse" value="<?= val('adresse') ?>" placeholder="Quartier, Avenue, N°" required>
                </div>
                <div class="form-group">
                  <label>Kijiji / Chapelle</label>
                  <select name="id_kijiji" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($kijijis as $k): ?>
                      <option value="<?= $k['id_kijiji'] ?>" <?= selected('id_kijiji', $k['id_kijiji']) ?>><?= $k['nom_kijiji'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Cellule</label>
                  <select name="id_cellule" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($cellules as $c): ?>
                      <option value="<?= $c['id_cellule'] ?>" <?= selected('id_cellule', $c['id_cellule']) ?>><?= $c['nom_cellule'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="step-buttons">
              <a href="index.php?step=2" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</a>
              <button type="submit" name="next_step" value="4" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
            </div>
          </div>

        <?php elseif ($step === 4): ?>
          <!-- Étape 4: Profil Socio-Professionnel -->
          <div class="form-step">
            <div class="form-section">
              <h3><i class="fas fa-briefcase"></i> Profil Socio-Professionnel</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label>Profession</label>
                  <select name="id_profession" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($professions as $p): ?>
                      <option value="<?= $p['id_profession'] ?>" <?= selected('id_profession', $p['id_profession']) ?>><?= $p['nom_profession'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Niveau d'étude / Faculté</label>
                  <select name="niveau_etude" id="niveau_etude_select" onchange="toggleNiveauEtudeOther()" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($niveauEtudeOptions as $opt): ?>
                      <option value="<?= htmlspecialchars($opt) ?>" <?= selected('niveau_etude', $opt) ?>><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group" id="niveau_etude_autre_group" style="display:none;">
                  <label>Précisez le niveau</label>
                  <input type="text" name="niveau_etude_autre" id="niveau_etude_autre" value="<?= val('niveau_etude_autre') ?>" placeholder="Ex: G3 Économie">
                </div>
                <div class="form-group">
                  <label>Situation de handicap</label>
                  <select name="handicape" required>
                    <option value="Aucun" <?= selected('handicape', 'Aucun') ?>>Aucun</option>
                    <option value="Physique" <?= selected('handicape', 'Physique') ?>>Physique</option>
                    <option value="Mental" <?= selected('handicape', 'Mental') ?>>Mental</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Vulnérabilité</label>
                  <select name="vulnerable" required>
                    <option value="Aucune" <?= selected('vulnerable', 'Aucune') ?>>Aucune</option>
                    <option value="3ème âge" <?= selected('vulnerable', '3ème âge') ?>>3ème âge</option>
                    <option value="Malade chronique" <?= selected('vulnerable', 'Malade chronique') ?>>Malade chronique</option>
                    <option value="Orphelin(e)" <?= selected('vulnerable', 'Orphelin(e)') ?>>Orphelin(e)</option>
                    <option value="Sans abri" <?= selected('vulnerable', 'Sans abri') ?>>Sans abri</option>
                    <option value="Veuve" <?= selected('vulnerable', 'Veuve') ?>>Veuve</option>
                    <option value="Femme espérance" <?= selected('vulnerable', 'Femme espérance') ?>>Femme espérance</option>
                    <option value="Homme espérance" <?= selected('vulnerable', 'Homme espérance') ?>>Homme espérance</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="step-buttons">
              <a href="index.php?step=3" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</a>
              <button type="submit" name="next_step" value="5" class="next-btn submit-final">
                <i class="fas fa-check-circle"></i> Valider l'enregistrement
              </button>
            </div>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <script>
    function toggleNiveauEtudeOther() {
      const select = document.getElementById('niveau_etude_select');
      const group = document.getElementById('niveau_etude_autre_group');
      const input = document.getElementById('niveau_etude_autre');
      if (!select || !group || !input) return;

      const isOther = select.value === 'Autre';
      group.style.display = isOther ? '' : 'none';
      input.disabled = !isOther;
      if (!isOther) input.value = '';
    }

    function toggleBaptemeFields() {
      const baptise = document.getElementById('baptise');
      if (!baptise) return;

      const isBaptise = baptise.value === "1";
      const dateBapteme = document.getElementById('date_bapteme');
      const communion = document.getElementById('communion');
      const paroisse = document.getElementById('paroisse_bapteme');

      if (dateBapteme) dateBapteme.disabled = !isBaptise;
      if (communion) communion.disabled = !isBaptise;
      if (paroisse) paroisse.disabled = !isBaptise;

      if (!isBaptise) {
        if (dateBapteme) dateBapteme.value = "";
        if (communion) communion.value = "0";
        if (paroisse) paroisse.value = "";
      }
    }

    window.onload = function() {
      toggleBaptemeFields();
      toggleNiveauEtudeOther();
    };
  </script>

</body>

</html>