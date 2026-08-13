-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 06/08/2026 às 14:12
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `trainhub`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `areas`
--

CREATE TABLE `areas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cor` varchar(7) NOT NULL DEFAULT '#1E40AF',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `areas`
--

INSERT INTO `areas` (`id`, `nome`, `descricao`, `cor`, `criado_em`, `atualizado_em`) VALUES
(1, 'Automação Industrial', 'Sistemas, CLP, robótica e instrumentação.', '#1E40AF', '2026-08-05 09:47:35', '2026-08-05 09:47:35'),
(2, 'Tecnologia da Informação', 'Redes, sistemas, programação e segurança.', '#065F46', '2026-08-05 09:47:35', '2026-08-05 09:47:35'),
(3, 'Segurança do Trabalho', 'NRs, EPIs, análise de risco e prevenção.', '#92400E', '2026-08-05 09:47:35', '2026-08-05 09:47:35'),
(4, 'Gestão e Negócios', 'Liderança, projetos e processos organizacionais.', '#5B21B6', '2026-08-05 09:47:35', '2026-08-05 09:47:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas`
--

CREATE TABLE `aulas` (
  `id` int(10) UNSIGNED NOT NULL,
  `treinamento_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `objetivo` text DEFAULT NULL,
  `conteudo` longtext DEFAULT NULL,
  `duracao_min` int(11) NOT NULL DEFAULT 0,
  `ordem` smallint(6) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `treinamento_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `arquivo` varchar(300) DEFAULT NULL,
  `nota_minima` decimal(5,2) NOT NULL DEFAULT 6.00,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `competencias`
--

CREATE TABLE `competencias` (
  `id` int(10) UNSIGNED NOT NULL,
  `treinamento_id` int(10) UNSIGNED NOT NULL,
  `descricao` text NOT NULL,
  `ordem` smallint(6) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `materiais`
--

CREATE TABLE `materiais` (
  `id` int(10) UNSIGNED NOT NULL,
  `aula_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL,
  `arquivo` varchar(300) NOT NULL,
  `tipo` varchar(10) NOT NULL,
  `tamanho_kb` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `treinamentos`
--

CREATE TABLE `treinamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `area_id` int(10) UNSIGNED NOT NULL,
  `criado_por` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `carga_horaria` decimal(6,1) NOT NULL DEFAULT 0.0,
  `status` enum('rascunho','publicado','arquivado') NOT NULL DEFAULT 'rascunho',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `senha_hash` varchar(255) DEFAULT NULL,
  `google_id` varchar(120) DEFAULT NULL,
  `auth_provider` enum('local','google') NOT NULL DEFAULT 'local',
  `perfil` enum('admin','instrutor') NOT NULL DEFAULT 'instrutor',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `google_id`, `auth_provider`, `perfil`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'Administrador', 'admin@senai-cic.br', '$2y$10$/SwU.UzQgTz6RRvqSjTwzuErFvcCPZOVes8Vxl1NiWMQIYmrGLwoG', NULL, 'local', 'admin', 1, '2026-08-05 09:47:35', '2026-08-05 09:47:35');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_treinamento` (`treinamento_id`);

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_treinamento` (`treinamento_id`);

--
-- Índices de tabela `competencias`
--
ALTER TABLE `competencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_treinamento` (`treinamento_id`);

--
-- Índices de tabela `materiais`
--
ALTER TABLE `materiais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aula` (`aula_id`);

--
-- Índices de tabela `treinamentos`
--
ALTER TABLE `treinamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_area` (`area_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_treinamento_usuario` (`criado_por`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_google_id` (`google_id`),
  ADD KEY `idx_perfil` (`perfil`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `competencias`
--
ALTER TABLE `competencias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `materiais`
--
ALTER TABLE `materiais`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `treinamentos`
--
ALTER TABLE `treinamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `fk_aula_treinamento` FOREIGN KEY (`treinamento_id`) REFERENCES `treinamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `fk_avaliacao_treinamento` FOREIGN KEY (`treinamento_id`) REFERENCES `treinamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `competencias`
--
ALTER TABLE `competencias`
  ADD CONSTRAINT `fk_competencia_treinamento` FOREIGN KEY (`treinamento_id`) REFERENCES `treinamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `materiais`
--
ALTER TABLE `materiais`
  ADD CONSTRAINT `fk_material_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `treinamentos`
--
ALTER TABLE `treinamentos`
  ADD CONSTRAINT `fk_treinamento_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_treinamento_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
