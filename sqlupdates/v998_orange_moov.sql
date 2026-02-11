-- Orange Money et Moov Money : ajout aux moyens de paiement
INSERT INTO `payment_methods` (`name`, `active`, `addon_identifier`, `created_at`, `updated_at`) VALUES
('orange', 0, NULL, NOW(), NOW()),
('moov', 0, NULL, NOW(), NOW());

-- Table pour les transactions Moov en attente de confirmation
CREATE TABLE IF NOT EXISTS `waiting_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `combined_order_id` bigint(20) UNSIGNED NOT NULL,
  `phone` varchar(50) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `waiting_transactions_combined_order_id_index` (`combined_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
