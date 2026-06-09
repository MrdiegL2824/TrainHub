<?php
$pageTitle = $pageTitle ?? 'TrainHub';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle); ?> | TrainHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="bg-slate-100 text-slate-900">
<div class="min-h-screen lg:flex">
