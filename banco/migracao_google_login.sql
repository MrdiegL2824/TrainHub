USE `TrainHub`;

ALTER TABLE `usuarios`
    MODIFY `senha_hash` VARCHAR(255) NULL,
    ADD COLUMN `google_id` VARCHAR(120) NULL UNIQUE AFTER `senha_hash`,
    ADD COLUMN `auth_provider` ENUM('local','google') NOT NULL DEFAULT 'local' AFTER `google_id`,
    ADD INDEX `idx_google_id` (`google_id`);

UPDATE `usuarios`
   SET `senha_hash` = '$2y$10$/SwU.UzQgTz6RRvqSjTwzuErFvcCPZOVes8Vxl1NiWMQIYmrGLwoG'
 WHERE `email` = 'admin@senai-cic.br';
