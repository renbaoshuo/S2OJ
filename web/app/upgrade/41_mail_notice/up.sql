CREATE TABLE `emails` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `receiver` varchar(20) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL,
  `send_time` datetime DEFAULT NULL,
  `priority` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `send_time` (`send_time`),
  KEY `priority` (`priority`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
