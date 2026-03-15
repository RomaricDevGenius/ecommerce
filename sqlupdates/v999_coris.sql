-- Coris Money : ajout au moyens de paiement
INSERT INTO `payment_methods` (`name`, `active`, `addon_identifier`, `created_at`, `updated_at`) VALUES
('coris', 0, NULL, NOW(), NOW());

