<?php
declare(strict_types=1);

$host = 'localhost';
$dbname = 'student_dormitory';
$dbuser = 'root';
$dbpass = ''; // Агар MySQL парол дошта бошад, ин ҷо ворид кунед.
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Пайвастшавӣ ба базаи маълумот ноком шуд. Танзимоти db.php-ро санҷед.');
}
