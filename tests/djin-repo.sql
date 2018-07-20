CREATE TABLE `djin-repo` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `array` json DEFAULT NULL,
  `money___amount` int(11) DEFAULT NULL,
  `money___currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balances` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;