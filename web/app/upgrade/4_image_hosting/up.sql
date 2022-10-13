ALTER TABLE `user_info` ADD COLUMN `images_size_limit` int(11) UNSIGNED NOT NULL DEFAULT 104857600 /* 100 MiB */;

--
-- Table structure for table `users_images`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(100) NOT NULL,
  `uploader` varchar(20) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `upload_time` datetime NOT NULL,
  `size` int(11) NOT NULL,
  `hash` varchar(70) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uploader` (`uploader`),
  KEY `path` (`path`),
  KEY `upload_time` (`upload_time`),
  KEY `size` (`size`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
