/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ciclos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ciclos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ciclos_data_inicio_unique` (`data_inicio`),
  KEY `ciclos_data_inicio_index` (`data_inicio`),
  KEY `ciclos_data_fim_index` (`data_fim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cliente_produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente_produto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint unsigned NOT NULL,
  `produto_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cliente_produto_cliente_id_produto_id_unique` (`cliente_id`,`produto_id`),
  KEY `cliente_produto_produto_id_foreign` (`produto_id`),
  CONSTRAINT `cliente_produto_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cliente_produto_produto_id_foreign` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `faturamento` decimal(15,2) DEFAULT NULL,
  `servico` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `honorario` decimal(15,2) DEFAULT NULL,
  `possibilidade` text COLLATE utf8mb4_unicode_ci,
  `tipo` tinyint(1) NOT NULL DEFAULT '0',
  `cpfcnpj` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regime_tributario` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo_encerramento` text COLLATE utf8mb4_unicode_ci,
  `data_encerramento` date DEFAULT NULL,
  `fator_r` tinyint(1) NOT NULL DEFAULT '0',
  `cliente_desde` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vencimento_certificado` date DEFAULT NULL,
  `dataabertura` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clientes_cnpj_unique` (`cpfcnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comentarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tarefa_id` bigint unsigned NOT NULL,
  `usuario_id` bigint unsigned NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comentarios_tarefa_id_index` (`tarefa_id`),
  KEY `comentarios_usuario_id_index` (`usuario_id`),
  CONSTRAINT `comentarios_tarefa_id_foreign` FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compromissos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compromissos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `data` date NOT NULL,
  `hora` time DEFAULT NULL,
  `cor` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3b82f6',
  `criado_por` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compromissos_criado_por_foreign` (`criado_por`),
  KEY `compromissos_data_index` (`data`),
  CONSTRAINT `compromissos_criado_por_foreign` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contato_clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contato_clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint unsigned NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('Dono','Sócio') COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gmail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contato_clientes_cliente_id_foreign` (`cliente_id`),
  CONSTRAINT `contato_clientes_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departamentos_nome_unique` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etapas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etapas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL,
  `cor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visivel` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etapas_funil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etapas_funil` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int unsigned NOT NULL DEFAULT '0',
  `cor` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6b7280',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historico_funil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_funil` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `etapa_anterior_id` bigint unsigned DEFAULT NULL,
  `etapa_nova_id` bigint unsigned NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `alterado_por` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `historico_funil_lead_id_foreign` (`lead_id`),
  KEY `historico_funil_etapa_anterior_id_foreign` (`etapa_anterior_id`),
  KEY `historico_funil_etapa_nova_id_foreign` (`etapa_nova_id`),
  KEY `historico_funil_alterado_por_foreign` (`alterado_por`),
  CONSTRAINT `historico_funil_alterado_por_foreign` FOREIGN KEY (`alterado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `historico_funil_etapa_anterior_id_foreign` FOREIGN KEY (`etapa_anterior_id`) REFERENCES `etapas_funil` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historico_funil_etapa_nova_id_foreign` FOREIGN KEY (`etapa_nova_id`) REFERENCES `etapas_funil` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `historico_funil_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lead_produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_produto` (
  `lead_id` bigint unsigned NOT NULL,
  `produto_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`lead_id`,`produto_id`),
  KEY `lead_produto_produto_id_foreign` (`produto_id`),
  CONSTRAINT `lead_produto_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_produto_produto_id_foreign` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `empresa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` tinyint NOT NULL DEFAULT '1',
  `cpfcnpj` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `faturamento` decimal(15,2) DEFAULT NULL,
  `servico` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `honorario` decimal(15,2) DEFAULT NULL,
  `possibilidade` text COLLATE utf8mb4_unicode_ci,
  `etapa_funil_id` bigint unsigned NOT NULL,
  `responsavel_id` bigint unsigned DEFAULT NULL,
  `origem` enum('manual','formulario') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `convertido_cliente_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_etapa_funil_id_foreign` (`etapa_funil_id`),
  KEY `leads_responsavel_id_foreign` (`responsavel_id`),
  KEY `leads_convertido_cliente_id_foreign` (`convertido_cliente_id`),
  CONSTRAINT `leads_convertido_cliente_id_foreign` FOREIGN KEY (`convertido_cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_etapa_funil_id_foreign` FOREIGN KEY (`etapa_funil_id`) REFERENCES `etapas_funil` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `leads_responsavel_id_foreign` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produtos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reltarefas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reltarefas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tarefa_id` bigint unsigned NOT NULL,
  `etapa_anterior_id` bigint unsigned DEFAULT NULL,
  `etapa_nova_id` bigint unsigned DEFAULT NULL,
  `responsavel_anterior_id` bigint unsigned DEFAULT NULL,
  `responsavel_novo_id` bigint unsigned DEFAULT NULL,
  `alterado_por` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reltarefas_tarefa_id_foreign` (`tarefa_id`),
  KEY `reltarefas_etapa_anterior_id_foreign` (`etapa_anterior_id`),
  KEY `reltarefas_etapa_nova_id_foreign` (`etapa_nova_id`),
  KEY `reltarefas_alterado_por_foreign` (`alterado_por`),
  KEY `reltarefas_responsavel_anterior_id_foreign` (`responsavel_anterior_id`),
  KEY `reltarefas_responsavel_novo_id_foreign` (`responsavel_novo_id`),
  CONSTRAINT `reltarefas_alterado_por_foreign` FOREIGN KEY (`alterado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `reltarefas_etapa_anterior_id_foreign` FOREIGN KEY (`etapa_anterior_id`) REFERENCES `etapas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reltarefas_etapa_nova_id_foreign` FOREIGN KEY (`etapa_nova_id`) REFERENCES `etapas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `reltarefas_responsavel_anterior_id_foreign` FOREIGN KEY (`responsavel_anterior_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reltarefas_responsavel_novo_id_foreign` FOREIGN KEY (`responsavel_novo_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reltarefas_tarefa_id_foreign` FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarefas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarefas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `cliente_id` bigint unsigned NOT NULL,
  `departamento_id` bigint unsigned NOT NULL,
  `etapa_id` bigint unsigned NOT NULL,
  `responsavel_id` bigint unsigned DEFAULT NULL,
  `supervisor_id` bigint unsigned DEFAULT NULL,
  `criado_por` bigint unsigned NOT NULL,
  `data_vencimento` date NOT NULL,
  `ciclo_id` bigint unsigned DEFAULT NULL,
  `passou_ciclo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indica que a tarefa foi transferida de um ciclo anterior',
  `data_conclusao` timestamp NULL DEFAULT NULL,
  `prioridade` tinyint NOT NULL DEFAULT '1',
  `atrasada` tinyint(1) NOT NULL DEFAULT '0',
  `recorrente` tinyint(1) NOT NULL DEFAULT '0',
  `frequencia` enum('nenhuma','diaria','semanal','mensal','trimestral','semestral','anual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nenhuma',
  `intervalo` int DEFAULT NULL COMMENT 'a cada quantos dias/semanas/meses',
  `tarefa_original_id` bigint unsigned DEFAULT NULL,
  `data_proxima_geracao` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tarefas_responsavel_id_foreign` (`responsavel_id`),
  KEY `tarefas_criado_por_foreign` (`criado_por`),
  KEY `tarefas_cliente_id_index` (`cliente_id`),
  KEY `tarefas_departamento_id_index` (`departamento_id`),
  KEY `tarefas_etapa_id_index` (`etapa_id`),
  KEY `tarefas_recorrente_index` (`recorrente`),
  KEY `tarefas_tarefa_original_id_index` (`tarefa_original_id`),
  KEY `tarefas_ciclo_id_index` (`ciclo_id`),
  KEY `tarefas_supervisor_id_foreign` (`supervisor_id`),
  CONSTRAINT `tarefas_ciclo_id_foreign` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tarefas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tarefas_criado_por_foreign` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tarefas_departamento_id_foreign` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tarefas_etapa_id_foreign` FOREIGN KEY (`etapa_id`) REFERENCES `etapas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tarefas_responsavel_id_foreign` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tarefas_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tarefas_tarefa_original_id_foreign` FOREIGN KEY (`tarefa_original_id`) REFERENCES `tarefas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo` enum('diretor','supervisor','analista','assistente','auxiliar','ti') COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_registro` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `departamento_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuarios_email_unique` (`email`),
  KEY `usuarios_departamento_id_foreign` (`departamento_id`),
  CONSTRAINT `usuarios_departamento_id_foreign` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'0001_01_02_000000_create_usuarios_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'0001_01_02_000001_create_departamentos_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'0001_01_02_000002_create_clientes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'0001_01_02_000003_create_etapas_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'0001_01_02_000004_create_tarefas_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'0001_01_02_000006_create_comentarios_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_03_25_163912_add_columns_to_usuarios_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_03_26_163923_alter_cpf_clientes_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_03_27_164622_create_reltarefas_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_03_30_125917_create_ciclos_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_03_30_125918_add_ciclo_columns_to_tarefas_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_03_30_135729_add_descricao_to_clientes_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_03_30_151953_alter_frequencia_enum_in_tarefas_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_04_01_161557_create_compromissos_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_04_01_172721_insert_etapa_transferido_ciclo',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_04_02_091310_add_supervisor_id_to_tarefas_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_04_02_095607_add_visivel_to_etapas_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_04_13_163244_add_departamento_to_usuarios_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_04_15_094204_add_responsavel_columns_to_reltarefas_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_04_15_111054_create_produtos_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_04_15_111112_create_cliente_produto_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_04_15_114142_add_fator_r_to_clientes_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_04_24_104453_create_etapas_funil_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_04_24_104457_create_leads_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_04_24_104502_create_historico_funil_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_04_24_104508_add_crm_fields_to_clientes_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_04_24_141001_add_tipo_cpfcnpj_to_leads_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_04_24_152519_create_lead_produto_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_03_26_193126_alter_cpf_clientes_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_05_06_000001_add_ti_to_cargo_enum',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_05_06_000002_add_vencimento_certificado_to_clientes_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_05_06_165648_add_encerramento_to_clientes_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_05_07_103533_create_contato_clientes_table',28);
