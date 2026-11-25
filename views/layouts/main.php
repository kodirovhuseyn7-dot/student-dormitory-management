<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'SDMS') ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
        }

        .container {
            width: min(1100px, 92%);
            margin: auto;
        }

        .site-header {
            background: #172554;
            color: white;
            padding: 22px 0;
        }

        .site-header h1 {
            margin: 0 0 15px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 18px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            min-height: 70vh;
            padding: 35px 0;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
        }

        .site-footer {
            background: #111827;
            color: white;
            padding: 20px 0;
            text-align: center;
        }

        .button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 9px 14px;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/header.php'; ?>

<main class="container">
    <?= $content ?>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>

</body>
</html>