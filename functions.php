<?php
require_once 'config.php';

/**
 * Récupère les options d'une table pour alimenter un <select>
 * @param string $table Nom de la table
 * @param string $idField Nom du champ ID
 * @param string $nameField Nom du champ affiché
 * @return array Liste des résultats
 */
function getOptions($table, $idField, $nameField) {
    global $pdo;

    // Sécurisation du nom de table et des colonnes
    $allowedTables = ['services', 'ministeres', 'kijiji', 'cellule', 'professions', 'lieux_culte'];
    if (!in_array($table, $allowedTables)) {
        return []; // On ne permet pas d'autres tables
    }

    $sql = "SELECT $idField, $nameField FROM $table ORDER BY $nameField";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // En cas d’erreur SQL, on retourne un tableau vide
        return [];
    }
}

/**
 * Génère un jeton CSRF et le stocke en session
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF est valide
 */
function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
?>