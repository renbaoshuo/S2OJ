-- MySQL dump 10.13  Distrib 5.7.25, for Linux (x86_64)
--
-- Host: localhost    Database: app_uoj233
-- ------------------------------------------------------
-- Server version	5.7.25-0ubuntu0.18.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+08:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `app_uoj233`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `app_uoj233` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `app_uoj233`;

--
-- Table structure for table `best_ac_submissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `best_ac_submissions` (
  `problem_id` int NOT NULL,
  `submitter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submission_id` int NOT NULL,
  `used_time` int NOT NULL,
  `used_memory` int NOT NULL,
  `tot_size` int NOT NULL,
  `shortest_id` int NOT NULL,
  `shortest_used_time` int NOT NULL,
  `shortest_used_memory` int NOT NULL,
  `shortest_tot_size` int NOT NULL,
  PRIMARY KEY (`problem_id`,`submitter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci TABLESPACE `innodb_system`;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `best_ac_submissions`
--

LOCK TABLES `best_ac_submissions` WRITE;
/*!40000 ALTER TABLE `best_ac_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `best_ac_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `active_time` datetime NOT NULL,
  `poster` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_md` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `zan` int NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL,
  `type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'B',
  PRIMARY KEY (`id`),
  KEY `post_time` (`post_time`),
  KEY `active_time` (`active_time`),
  KEY `poster` (`poster`,`is_hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs_comments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blog_id` int NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `poster` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zan` int NOT NULL,
  `reply_id` int NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `reason_to_hide` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reply_id` (`reply_id`,`id`),
  KEY `blog_id` (`blog_id`,`post_time`),
  KEY `blog_id_2` (`blog_id`,`reply_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs_comments`
--

LOCK TABLES `blogs_comments` WRITE;
/*!40000 ALTER TABLE `blogs_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `blogs_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs_tags`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blog_id` int NOT NULL,
  `tag` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `blog_id` (`blog_id`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs_tags`
--

LOCK TABLES `blogs_tags` WRITE;
/*!40000 ALTER TABLE `blogs_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `blogs_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `click_zans`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `click_zans` (
  `type` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `val` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`type`,`target_id`,`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `click_zans`
--

LOCK TABLES `click_zans` WRITE;
/*!40000 ALTER TABLE `click_zans` DISABLE KEYS */;
/*!40000 ALTER TABLE `click_zans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime GENERATED ALWAYS AS ((`start_time` + interval `last_min` minute)) VIRTUAL NOT NULL,
  `last_min` int NOT NULL,
  `player_num` int NOT NULL DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra_config` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '{}',
  `zan` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests`
--

LOCK TABLES `contests` WRITE;
/*!40000 ALTER TABLE `contests` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests_asks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests_asks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contest_id` int NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `post_time` datetime NOT NULL,
  `reply_time` datetime NOT NULL,
  `is_hidden` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `contest_id` (`contest_id`,`is_hidden`,`username`) USING BTREE,
  KEY `username` (`username`,`contest_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests_asks`
--

LOCK TABLES `contests_asks` WRITE;
/*!40000 ALTER TABLE `contests_asks` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests_asks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests_notice`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests_notice` (
  `contest_id` int NOT NULL,
  `title` varchar(30) NOT NULL,
  `content` varchar(500) NOT NULL,
  `time` datetime NOT NULL,
  KEY `contest_id` (`contest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests_notice`
--

LOCK TABLES `contests_notice` WRITE;
/*!40000 ALTER TABLE `contests_notice` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests_notice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests_permissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests_permissions` (
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contest_id` int NOT NULL,
  PRIMARY KEY (`username`,`contest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests_permissions`
--

LOCK TABLES `contests_permissions` WRITE;
/*!40000 ALTER TABLE `contests_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests_problems`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests_problems` (
  `problem_id` int NOT NULL,
  `contest_id` int NOT NULL,
  `level` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`problem_id`,`contest_id`),
  KEY `contest_id` (`contest_id`,`problem_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests_problems`
--

LOCK TABLES `contests_problems` WRITE;
/*!40000 ALTER TABLE `contests_problems` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests_problems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests_registrants`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests_registrants` (
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contest_id` int NOT NULL,
  `has_participated` tinyint(1) NOT NULL,
  `final_rank` int NOT NULL,
  PRIMARY KEY (`contest_id`,`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests_registrants`
--

LOCK TABLES `contests_registrants` WRITE;
/*!40000 ALTER TABLE `contests_registrants` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests_registrants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests_submissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests_submissions` (
  `contest_id` int NOT NULL,
  `submitter` varchar(20) NOT NULL,
  `problem_id` int NOT NULL,
  `submission_id` int NOT NULL,
  `score` int NOT NULL,
  `penalty` int NOT NULL,
  `cnt` int DEFAULT NULL,
  `n_failures` int DEFAULT NULL,
  PRIMARY KEY (`contest_id`,`submitter`,`problem_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests_submissions`
--

LOCK TABLES `contests_submissions` WRITE;
/*!40000 ALTER TABLE `contests_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests_reviews`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests_reviews` (
  `contest_id` int NOT NULL,
  `problem_id` int NOT NULL DEFAULT 0,
  `poster` varchar(20) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`contest_id`,`problem_id`,`poster`),
  KEY `contest_id` (`contest_id`,`problem_id`),
  KEY `poster` (`poster`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests_reviews`
--

LOCK TABLES `contests_reviews` WRITE;
/*!40000 ALTER TABLE `contests_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `contests_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countdowns`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countdowns` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `end_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countdowns`
--

LOCK TABLES `countdowns` WRITE;
/*!40000 ALTER TABLE `countdowns` DISABLE KEYS */;
/*!40000 ALTER TABLE `countdowns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_test_submissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_test_submissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `problem_id` int UNSIGNED NOT NULL,
  `submit_time` datetime NOT NULL,
  `submitter` varchar(20) NOT NULL,
  `content` text NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `result` blob NOT NULL,
  `status` varchar(20) NOT NULL,
  `status_details` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `submitter` (`submitter`,`problem_id`,`id`),
  KEY `judge_time` (`judge_time`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_test_submissions`
--

LOCK TABLES `custom_test_submissions` WRITE;
/*!40000 ALTER TABLE `custom_test_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_test_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friend_links`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `friend_links` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(40) NOT NULL,
  `url` varchar(100) NOT NULL,
  `level` int NOT NULL DEFAULT 10,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friend_links`
--

LOCK TABLES `friend_links` WRITE;
/*!40000 ALTER TABLE `friend_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `friend_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `announcement` text NOT NULL DEFAULT '',
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_assignments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups_assignments` (
  `group_id` int NOT NULL,
  `list_id` int NOT NULL,
  `end_time` datetime NOT NULL,
  PRIMARY KEY (`group_id`, `list_id`),
  KEY `list_id` (`list_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups_users` (
  `group_id` int NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`group_id`, `username`),
  KEY `group_id` (`group_id`, `username`),
  KEY `username` (`username`, `group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hacks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hacks` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `problem_id` int UNSIGNED NOT NULL,
  `contest_id` int UNSIGNED DEFAULT NULL,
  `submission_id` int UNSIGNED NOT NULL,
  `hacker` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_type` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submit_time` datetime NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` blob NOT NULL,
  `is_hidden` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `is_hidden` (`is_hidden`,`problem_id`),
  KEY `status` (`status`),
  KEY `judge_time` (`judge_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hacks`
--

LOCK TABLES `hacks` WRITE;
/*!40000 ALTER TABLE `hacks` DISABLE KEYS */;
/*!40000 ALTER TABLE `hacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `important_blogs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `important_blogs` (
  `blog_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  PRIMARY KEY (`blog_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `important_blogs`
--

LOCK TABLES `important_blogs` WRITE;
/*!40000 ALTER TABLE `important_blogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `important_blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `judger_info`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `judger_info` (
  `judger_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `display_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`judger_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `judger_info`
--

LOCK TABLES `judger_info` WRITE;
/*!40000 ALTER TABLE `judger_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `judger_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta`
--

CREATE TABLE `meta` (
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meta`
--

LOCK TABLES `meta` WRITE;
/*!40000 ALTER TABLE `meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `problems`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `problems` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploader` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `submission_requirement` mediumtext COLLATE utf8mb4_unicode_ci,
  `hackable` tinyint(1) NOT NULL DEFAULT '0',
  `extra_config` json NOT NULL,
  `zan` int NOT NULL DEFAULT '0',
  `ac_num` int NOT NULL DEFAULT '0',
  `submit_num` int NOT NULL DEFAULT '0',
  `difficulty` int NOT NULL DEFAULT '-1',
  `assigned_to_judger` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'any',
  PRIMARY KEY (`id`),
  KEY `assigned_to_judger` (`assigned_to_judger`),
  KEY `uploader` (`uploader`),
  KEY `difficulty` (`difficulty`),
  KEY `is_hidden` (`is_hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci TABLESPACE `innodb_system`;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `problems`
--

LOCK TABLES `problems` WRITE;
/*!40000 ALTER TABLE `problems` DISABLE KEYS */;
/*!40000 ALTER TABLE `problems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `problems_contents`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `problems_contents` (
  `id` int NOT NULL,
  `statement` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `statement_md` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `problems_contents`
--

LOCK TABLES `problems_contents` WRITE;
/*!40000 ALTER TABLE `problems_contents` DISABLE KEYS */;
/*!40000 ALTER TABLE `problems_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `problems_permissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `problems_permissions` (
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `problem_id` int NOT NULL,
  PRIMARY KEY (`username`,`problem_id`),
  KEY `problem_id` (`problem_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `problems_permissions`
--

LOCK TABLES `problems_permissions` WRITE;
/*!40000 ALTER TABLE `problems_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `problems_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `problems_solutions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `problems_solutions` (
  `problem_id` int NOT NULL,
  `blog_id` int NOT NULL,
  PRIMARY KEY (`problem_id`, `blog_id`),
  UNIQUE KEY `unique__blog_id` (`blog_id`),
  KEY `problem_id` (`problem_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `problems_solutions`
--

LOCK TABLES `problems_solutions` WRITE;
/*!40000 ALTER TABLE `problems_solutions` DISABLE KEYS */;
/*!40000 ALTER TABLE `problems_solutions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `problems_tags`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `problems_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `problem_id` int NOT NULL,
  `tag` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `problem_id` (`problem_id`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `problems_tags`
--

LOCK TABLES `problems_tags` WRITE;
/*!40000 ALTER TABLE `problems_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `problems_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lists`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `description` text NOT NULL DEFAULT '',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lists_contents`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lists_contents` (
  `id` int NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_md` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lists_contents`
--

LOCK TABLES `lists_contents` WRITE;
/*!40000 ALTER TABLE `lists_contents` DISABLE KEYS */;
/*!40000 ALTER TABLE `lists_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lists_problems`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lists_problems` (
  `list_id` int NOT NULL,
  `problem_id` int NOT NULL,
  PRIMARY KEY (`list_id`, `problem_id`),
  KEY `list_id` (`list_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lists_problems`
--

LOCK TABLES `lists_problems` WRITE;
/*!40000 ALTER TABLE `lists_problems` DISABLE KEYS */;
/*!40000 ALTER TABLE `lists_problems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lists_tags`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lists_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `list_id` int NOT NULL,
  `tag` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `list_id` (`list_id`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lists_tags`
--

LOCK TABLES `lists_tags` WRITE;
/*!40000 ALTER TABLE `lists_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `lists_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_requests`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `remote_addr` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('search','autocomplete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cache_id` int NOT NULL,
  `q` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `result` json NOT NULL,
  PRIMARY KEY (`id`),
  KEY `remote_addr` (`remote_addr`,`created_at`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_requests`
--

LOCK TABLES `search_requests` WRITE;
/*!40000 ALTER TABLE `search_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `problem_id` int UNSIGNED NOT NULL,
  `contest_id` int UNSIGNED DEFAULT NULL,
  `submit_time` datetime NOT NULL,
  `submitter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tot_size` int NOT NULL,
  `judge_reason` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `judge_time` datetime DEFAULT NULL,
  `judger` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result` mediumblob NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `result_error` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int DEFAULT NULL,
  `hide_score_to_others` tinyint(1) NOT NULL DEFAULT '0',
  `hidden_score` int DEFAULT NULL,
  `used_time` int NOT NULL DEFAULT '0',
  `used_memory` int NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL,
  `status_details` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`id`),
  KEY `result_error` (`result_error`),
  KEY `problem_id` (`problem_id`,`id`),
  KEY `language` (`language`,`id`),
  KEY `language2` (`is_hidden`,`language`,`id`),
  KEY `user_score` (`problem_id`,`submitter`,`score`,`id`),
  KEY `problem_id2` (`is_hidden`,`problem_id`,`id`),
  KEY `id2` (`is_hidden`,`id`),
  KEY `problem_score2` (`is_hidden`,`problem_id`,`score`,`id`),
  KEY `contest_submission_status` (`contest_id`,`status`),
  KEY `submitter2` (`is_hidden`,`submitter`,`id`),
  KEY `submitter` (`submitter`,`id`) USING BTREE,
  KEY `contest_id` (`contest_id`,`is_hidden`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions_history`
--

CREATE TABLE `submissions_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` int UNSIGNED NOT NULL,
  `judge_reason` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `judge_time` datetime DEFAULT NULL,
  `judger` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result` mediumblob NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_details` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result_error` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int DEFAULT NULL,
  `used_time` int NOT NULL DEFAULT '0',
  `used_memory` int NOT NULL DEFAULT '0',
  `major` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_judge_time` (`submission_id`,`judge_time`,`id`),
  KEY `submission` (`submission_id`,`id`),
  KEY `status_major` (`status`,`major`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions_history`
--

LOCK TABLES `submissions_history` WRITE;
/*!40000 ALTER TABLE `submissions_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissions_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_updates`
--

CREATE TABLE `system_updates` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int UNSIGNED NOT NULL,
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_id_time` (`type`,`target_id`,`time`),
  KEY `type_time` (`type`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_updates`
--

LOCK TABLES `system_updates` WRITE;
/*!40000 ALTER TABLE `system_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `upgrades`
--

CREATE TABLE `upgrades` (
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('up','down') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `upgrades`
--

LOCK TABLES `upgrades` WRITE;
/*!40000 ALTER TABLE `upgrades` DISABLE KEYS */;
INSERT INTO `upgrades` (`name`, `status`, `updated_at`) VALUES
  ('3_parsedown', 'up', now()),
  ('4_image_hosting', 'up', now()),
  ('6_user_info_v2', 'up', now()),
  ('8_group_v2', 'up', now()),
  ('9_list_v2', 'up', now()),
  ('14_sync_from_uoj.ac', 'up', now()),
  ('16_list_v3', 'up', now()),
  ('18_user_permissions', 'up', now()),
  ('20_problem_difficulty', 'up', now()),
  ('21_problem_difficulty', 'up', now());
/*!40000 ALTER TABLE `upgrades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_info`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_info` (
  `usergroup` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'U',
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usertype` enum('student','teacher','system','banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `realname` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `school` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `svn_password` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qq` bigint NOT NULL DEFAULT '0',
  `sex` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'U',
  `ac_num` int NOT NULL DEFAULT 0,
  `register_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_visit_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `expiration_time` datetime DEFAULT NULL,
  `remote_addr` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `http_x_forwarded_for` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remember_token` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `motto` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extra` json NOT NULL,
  PRIMARY KEY (`username`),
  KEY `ac_num` (`ac_num`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_info`
--

LOCK TABLES `user_info` WRITE;
/*!40000 ALTER TABLE `user_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_images`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_images` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `path` varchar(100) NOT NULL,
  `uploader` varchar(20) NOT NULL,
  `width` int NOT NULL,
  `height` int NOT NULL,
  `upload_time` datetime NOT NULL,
  `size` int NOT NULL,
  `hash` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uploader` (`uploader`),
  KEY `path` (`path`),
  KEY `upload_time` (`upload_time`),
  KEY `size` (`size`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_images`
--

LOCK TABLES `users_images` WRITE;
/*!40000 ALTER TABLE `users_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_msg`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_msg` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender` varchar(20) NOT NULL,
  `receiver` varchar(20) NOT NULL,
  `message` varchar(5000) NOT NULL,
  `send_time` datetime NOT NULL,
  `read_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sender` (`sender`),
  KEY `receiver` (`receiver`),
  KEY `read_time` (`receiver`,`read_time`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_msg`
--

LOCK TABLES `user_msg` WRITE;
/*!40000 ALTER TABLE `user_msg` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_msg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_system_msg`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_system_msg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_time` datetime NOT NULL,
  `read_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receiver` (`receiver`,`read_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_system_msg`
--

LOCK TABLES `user_system_msg` WRITE;
/*!40000 ALTER TABLE `user_system_msg` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_system_msg` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
