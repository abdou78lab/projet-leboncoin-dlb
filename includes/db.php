<?php
$host = 'localhost';
$dbname = 'danslebueno';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erreur connexion BDD : ' . $e->getMessage());
}