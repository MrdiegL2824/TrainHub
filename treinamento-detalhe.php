<?php
$curso = "Curso 2";
$progresso = 50;

$modulos = [
    [
        "id" => 1,
        "nome" => "Modulo 1",
        "concluido" => true,
        "arquivos" => []
    ],
    [
        "id" => 2,
        "nome" => "Modulo 2",
        "concluido" => true,
        "arquivos" => []
    ],
    [
        "id" => 3,
        "nome" => "Modulo 3",
        "concluido" => true,
        "arquivos" => []
    ],
    [
        "id" => 4,
        "nome" => "Modulo 4",
        "concluido" => false,
        "arquivos" => [
            [
                "tipo" => "pdf",
                "nome" => "Arquivo pdf",
                "arquivo" => "arquivos/material.pdf"
            ],
            [
                "tipo" => "mp4",
                "nome" => "Arquivo Mp4",
                "arquivo" => "arquivos/video.mp4"
            ]
        ]
    ],
    [
        "id" => 5,
        "nome" => "Modulo 5",
        "concluido" => false,
        "arquivos" => []
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($curso) ?></title>
    <!-- Alpine.js carregado de forma assíncrona para gerenciar a interatividade -->
    <script defer src="https://jsdelivr.net"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f5f7fb;
            color: #34436f;
            overflow: hidden;
            height: 100vh;
        }

        /* 1. ESTILIZAÇÃO DO CABEÇALHO */
        .header {
            height: 48px;
            background: #e2e8f0;
            border-b: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            color: #2d3e63;
        }

        .logo-icon {
            width: 18px;
            height: 18px;
        }

        .menu-top {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-left: 140px;
            flex: 1;
        }

        .menu-top a {
            text-decoration: none;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.15s;
        }

        .menu-top a.active, .menu-top a:hover {
            color: #2d3e63;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #000;
            border: 1px solid #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 10px;
        }

        /* 2. LAYOUT DA PÁGINA */
        .page {
            height: calc(100vh - 48px);
            display: flex;
        }

        .content-area {
            flex: 1;
            padding: 40px 24px 0 24px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .back-button {
            position: absolute;
            top: 10px;
            left: 24px;
            text-decoration: none;
            color: #2d3e63;
            transition: opacity 0.2s;
        }

        .back-button:hover {
            opacity: 0.7;
        }

        .back-icon {
            width: 20px;
            height: 20px;
        }

        .content-box {
            flex: 1;
            background: #ffffff;
            border: 16px solid #edf1f7;
            border-radius: 24px 24px 0 0;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .content-inner {
            width: 100%;
            height: 100%;
            padding: 24px;
            overflow-y: auto;
        }

        /* 3. BARRA LATERAL DE MÓDULOS */
        .sidebar {
            width: 290px;
            background: #edf1f7;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #dcdfe4;
        }

        .course-header {
            background: #e2e8f0;
            margin: 12px 12px 6px 12px;
            padding: 14px 16px;
            border-radius: 12px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .medal-icon {
            width: 16px;
            height: 16px;
            color: #64748b;
        }

        .course-header h1 {
            font-size: 14px;
            font-weight: 700;
            color: #2d3e63;
        }

        .progress {
            position: absolute;
            right: 14px;
            top: 10px;
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
        }

        .modules-container {
            flex: 1;
            overflow-y: auto;
            padding: 6px 12px;
        }

        .module {
            background: #e2e8f0;
            border-radius: 12px;
            margin-bottom: 8px;
            overflow: hidden;
        }

        .module-header {
            width: 100%;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            cursor: pointer;
            text-align: left;
            transition: background 0.15s;
        }

        .module-header:hover {
            background: rgba(0,0,0,0.03);
        }

        .module-header.active-header {
            background: #cbd5e1;
        }

        .check-icon {
            width: 14px;
            height: 14px;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .check-done { color: #10b981; }
        .check-empty { color: transparent; }

        .module-name {
            flex: 1;
            font-size: 13px;
            font-weight: 700;
            color: #2d3e63;
        }

        .arrow-icon {
            width: 14px;
            height: 14px;
            color: #2d3e63;
            transition: transform 0.2s;
        }

        .rotate-arrow {
            transform: rotate(180deg);
        }

        /* 4. ARQUIVOS SUB-ITENS */
        .module-content {
            border-top: 1px solid #cbd5e1;
        }

        .file {
            height: 38px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 24px;
            color: #475569;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            transition: background 0.15s;
        }

        .file:hover {
            background: rgba(0,0,0,0.04);
        }

        .file.active-file {
            background: #94a3b8;
            color: #2d3e63;
        }

        .file-icon {
            width: 14px;
            height: 14px;
            color: #2d3e63;
            opacity: 0.7;
            flex-shrink: 0;
        }

        /* CUSTOMIZAÇÃO DE SCROLL */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>

<!-- Alpine.js inicializa com o Módulo 4 aberto conforme o seu print -->
<body class="bg-[#f8fafc]" x-data="{ moduloAberto: 4 }">

    <!-- 1. BARRA SUPERIOR (NAVBAR) -->
    <header class="header">
        <div class="logo-area">
            <!-- Ícone de Rede/Nó Superior Esquerdo Conforme a Imagem -->
                <div class="barra-logo">
                <img src="assets/logo.png" alt="Logo">
                <span>TrainHub</span>
            </div>
        </div>
        
        <nav class="menu-top">
            <a href="dashboard.php">
                <svg class="logo-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Página inicial</span>
            </a>
            <a href="#" class="active">
                <svg class="logo-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                <span>Meus Cursos</span>
                <svg style="width: 10px; height: 10px;" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </a>
        </nav>

        <div class="user-area">
            <span>TrainHub_User01</span>
            <div class="user-avatar">O</div>
        </div>
    </header>

    <!-- 2. LAYOUT DO CONTEÚDO -->
    <div class="page">
        
        <!-- ÁREA CENTRAL DA ESQUERDA (PLAYER / BRANCO) -->