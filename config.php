<?php
$host = "localhost";
$dbname = "church";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // Affiche les erreurs SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch par défaut en tableau associatif
            PDO::ATTR_EMULATE_PREPARES => false // Meilleure sécurité pour les requêtes préparées
        ]
    );

} catch (PDOException $e) {
    die("<strong>Erreur de connexion :</strong> " . $e->getMessage());
}
?>