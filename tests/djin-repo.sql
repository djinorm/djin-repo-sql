CREATE TABLE `djin-repo` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `array` json DEFAULT NULL,
  `money` TINYINT(1) DEFAULT NULL,
  `money___amount` int(11) DEFAULT NULL,
  `money___currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balances` text COLLATE utf8mb4_unicode_ci,
  `nested` TINYINT(1) DEFAULT NULL,
  `nested___money` TINYINT(1) DEFAULT NULL,
  `nested___money___amount` int(11) NULL,
  `nested___money___currency` char(3) COLLATE utf8mb4_unicode_ci NULL,
  `nested___array` text COLLATE utf8mb4_unicode_ci NULL,
  `nested_must` TINYINT(1) DEFAULT NULL,
  `nested_must___money`  TINYINT(1) DEFAULT NULL,
  `nested_must___money___amount` int(11) NULL,
  `nested_must___money___currency` char(3) COLLATE utf8mb4_unicode_ci NULL,
  `nested_must___array` text COLLATE utf8mb4_unicode_ci NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;