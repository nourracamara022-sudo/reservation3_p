<?php
$serveur = "localhost";
$base_de_donnees = "reservation";
$utilisateur = "root";
$mot_de_passe = "";

try {
    $pdo = new PDO(
        "mysql:host=$serveur;dbname=$base_de_donnees;charset=utf8mb4",
        $utilisateur,
        $mot_de_passe
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>