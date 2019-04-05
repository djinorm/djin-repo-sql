CREATE TABLE `djin-repo` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Array` json DEFAULT NULL,
  `Money` TINYINT(1) DEFAULT NULL,
  `Money___Amount` int(11) DEFAULT NULL,
  `Money___Currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Balances` text COLLATE utf8mb4_unicode_ci,
  `Nested` TINYINT(1) DEFAULT NULL,
  `Nested___Money` TINYINT(1) DEFAULT NULL,
  `Nested___Money___Amount` int(11) NULL,
  `Nested___Money___Currency` char(3) COLLATE utf8mb4_unicode_ci NULL,
  `Nested___Array` text COLLATE utf8mb4_unicode_ci NULL,
  `Nested_must` TINYINT(1) DEFAULT NULL,
  `Nested_must___Money`  TINYINT(1) DEFAULT NULL,
  `Nested_must___Money___Amount` int(11) NULL,
  `Nested_must___Money___Currency` char(3) COLLATE utf8mb4_unicode_ci NULL,
  `Nested_must___Array` text COLLATE utf8mb4_unicode_ci NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;