
CREATE DATABASE IF NOT EXISTS `TrainHub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `TrainHub`;

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`          VARCHAR(120)    NOT NULL,
    `email`         VARCHAR(180)    NOT NULL UNIQUE,
    `senha_hash`    VARCHAR(255)    NULL,
    `google_id`     VARCHAR(120)    NULL UNIQUE,
    `auth_provider` ENUM('local','google') NOT NULL DEFAULT 'local',
    `perfil`        ENUM('admin','instrutor') NOT NULL DEFAULT 'instrutor',
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_google_id` (`google_id`),
    INDEX `idx_perfil` (`perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `areas` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`          VARCHAR(100)    NOT NULL,
    `descricao`     TEXT,
    `cor`           VARCHAR(7)      NOT NULL DEFAULT '#1E40AF', -- hex
    `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `treinamentos` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `area_id`       INT UNSIGNED    NOT NULL,
    `criado_por`    INT UNSIGNED    NOT NULL,
    `titulo`        VARCHAR(200)    NOT NULL,
    `descricao`     TEXT,
    `carga_horaria` DECIMAL(6,1)    NOT NULL DEFAULT 0,
    `status`        ENUM('rascunho','publicado','arquivado') NOT NULL DEFAULT 'rascunho',
    `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_area` (`area_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_treinamento_area`    FOREIGN KEY (`area_id`)    REFERENCES `areas`    (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_treinamento_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `competencias` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `treinamento_id` INT UNSIGNED   NOT NULL,
    `descricao`     TEXT            NOT NULL,
    `ordem`         SMALLINT        NOT NULL DEFAULT 0,
    `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_treinamento` (`treinamento_id`),
    CONSTRAINT `fk_competencia_treinamento` FOREIGN KEY (`treinamento_id`) REFERENCES `treinamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aulas` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `treinamento_id` INT UNSIGNED   NOT NULL,
    `titulo`        VARCHAR(200)    NOT NULL,
    `objetivo`      TEXT,
    `conteudo`      LONGTEXT,
    `duracao_min`   INT             NOT NULL DEFAULT 0,
    `ordem`         SMALLINT        NOT NULL DEFAULT 0,
    `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_treinamento` (`treinamento_id`),
    CONSTRAINT `fk_aula_treinamento` FOREIGN KEY (`treinamento_id`) REFERENCES `treinamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `materiais` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `aula_id`       INT UNSIGNED    NOT NULL,
    `nome`          VARCHAR(200)    NOT NULL,
    `arquivo`       VARCHAR(300)    NOT NULL,
    `tipo`          VARCHAR(10)     NOT NULL,
    `tamanho_kb`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_aula` (`aula_id`),
    CONSTRAINT `fk_material_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `avaliacoes` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `treinamento_id` INT UNSIGNED   NOT NULL,
    `titulo`        VARCHAR(200)    NOT NULL,
    `descricao`     TEXT,
    `arquivo`       VARCHAR(300),
    `nota_minima`   DECIMAL(5,2)    NOT NULL DEFAULT 6.00,
    `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_treinamento` (`treinamento_id`),
    CONSTRAINT `fk_avaliacao_treinamento` FOREIGN KEY (`treinamento_id`) REFERENCES `treinamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `usuarios` (`nome`, `email`, `senha_hash`, `perfil`) VALUES
('Administrador', 'admin@senai-cic.br', '$2y$10$/SwU.UzQgTz6RRvqSjTwzuErFvcCPZOVes8Vxl1NiWMQIYmrGLwoG', 'admin');

INSERT IGNORE INTO `areas` (`nome`, `descricao`, `cor`) VALUES
('Automação Industrial',  'Sistemas, CLP, robótica e instrumentação.', '#1E40AF'),
('Tecnologia da Informação', 'Redes, sistemas, programação e segurança.', '#065F46'),
('Segurança do Trabalho', 'NRs, EPIs, análise de risco e prevenção.', '#92400E'),
('Gestão e Negócios',    'Liderança, projetos e processos organizacionais.', '#5B21B6');
