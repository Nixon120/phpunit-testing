CREATE DATABASE IF NOT EXISTS `mpadmin`;
CREATE DATABASE IF NOT EXISTS `api-batch`;
CREATE DATABASE IF NOT EXISTS `transaction-email`;
CREATE DATABASE IF NOT EXISTS `catalog`;
CREATE DATABASE IF NOT EXISTS `image`;
CREATE DATABASE IF NOT EXISTS `program-catalog`;
CREATE DATABASE IF NOT EXISTS `avs`;
CREATE DATABASE IF NOT EXISTS `program-content`;
CREATE DATABASE IF NOT EXISTS `ca-admin`;
CREATE DATABASE IF NOT EXISTS `rcs_admin`;
CREATE DATABASE IF NOT EXISTS `report`;
CREATE DATABASE IF NOT EXISTS `ssn`;

CREATE TABLE `Address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `reference_id` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address1` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address2` varchar(75) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQShipping` (`reference_id`,`participant_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Adjustment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `amount` decimal(10,5) NOT NULL,
  `type` int(1) NOT NULL,
  `active` int(1) NOT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `transaction_id` int(11) DEFAULT NULL,
  `reference` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`participant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=210 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `AutoRedemption` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `product_sku` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interval` int(1) NOT NULL COMMENT '1 = schedule, 2 = instant',
  `schedule` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `all_participant` int(1) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reference_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_id` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `url` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQDomainKey` (`organization_id`,`url`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `FeaturedProduct` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) NOT NULL,
  `sku` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_id` (`program_id`,`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `LayoutRow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) NOT NULL,
  `priority` int(2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_id` (`program_id`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `LayoutRowCard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `row_id` int(11) NOT NULL,
  `priority` int(2) NOT NULL,
  `size` int(2) NOT NULL,
  `type` varchar(20) NOT NULL,
  `product` varchar(45) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `product_row` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `row_id` (`row_id`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `Organization` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lft` int(11) DEFAULT NULL,
  `rgt` int(11) DEFAULT NULL,
  `lvl` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `unique_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_contact_reference` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accounts_payable_contact_reference` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  KEY `IXOrganization` (`name`) USING BTREE,
  KEY `company_contact_reference` (`company_contact_reference`),
  KEY `accounts_payable_contact_reference` (`accounts_payable_contact_reference`),
  CONSTRAINT `Organization_ibfk_1` FOREIGN KEY (`company_contact_reference`) REFERENCES `Contact` (`reference_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `Organization_ibfk_2` FOREIGN KEY (`accounts_payable_contact_reference`) REFERENCES `Contact` (`reference_id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Participant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `email_address` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(75) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unique_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit` decimal(10,2) DEFAULT '0.00',
  `firstname` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `phone` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_reference` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IXLogin` (`email_address`,`password`),
  KEY `IXClientID` (`program_id`),
  KEY `IXUniqueID` (`unique_id`) USING BTREE,
  KEY `IXName` (`firstname`,`lastname`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ParticipantMeta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_2` (`participant_id`,`key`),
  KEY `user_id` (`participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ProductCriteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) NOT NULL,
  `filter` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `Program` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) DEFAULT NULL,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `point` int(11) DEFAULT NULL,
  `address1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domain_id` int(11) DEFAULT NULL,
  `meta` longtext COLLATE utf8mb4_unicode_ci,
  `logo` blob,
  `active` tinyint(1) DEFAULT '1',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `unique_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_reference` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_to` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT 'Top Level Client',
  `deposit_amount` int(11) NOT NULL DEFAULT '0',
  `issue_1099` tinyint(1) NOT NULL DEFAULT '0',
  `employee_payroll_file` tinyint(1) NOT NULL DEFAULT '0',
  `cost_center_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_id` (`organization_id`,`unique_id`),
  KEY `IXClientKey` (`name`,`created_at`) USING BTREE,
  KEY `contact_reference` (`contact_reference`),
  CONSTRAINT `Program_ibfk_1` FOREIGN KEY (`contact_reference`) REFERENCES `Contact` (`reference_id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RangedPricingProduct` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(60) NOT NULL,
  `min` decimal(10,2) NOT NULL,
  `max` decimal(10,2) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `Shipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) DEFAULT NULL,
  `transaction_item_id` int(11) DEFAULT NULL,
  `vendor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Sweepstake` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) NOT NULL,
  `active` int(1) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `point` int(11) DEFAULT NULL,
  `max_participant_entry` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(6) NOT NULL DEFAULT 'manual',
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_id` (`program_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `SweepstakeDraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sweepstake_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `draw_count` int(11) NOT NULL,
  `processed` int(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sweepstake_id` (`sweepstake_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `SweepstakeEntry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sweepstake_id` int(11) NOT NULL,
  `sweepstake_draw_id` int(11) DEFAULT NULL,
  `participant_id` int(11) NOT NULL,
  `point` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sweepstake_draw_id` (`sweepstake_draw_id`),
  KEY `participant_id` (`participant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `Transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `wholesale` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `email_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=checkout, 2=credit, 3=debit',
  `verified` tinyint(1) DEFAULT '0' COMMENT '0 = false, 1 = true',
  `bypass_conditions` tinyint(1) DEFAULT '0',
  `completed` tinyint(1) DEFAULT '0',
  `processed` tinyint(1) DEFAULT '1',
  `shipping_reference` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `notes` mediumtext COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unique_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IXDate` (`created_at`) USING BTREE,
  KEY `IXUserID` (`participant_id`) USING BTREE,
  KEY `decimal` (`wholesale`) USING BTREE,
  KEY `IXModifiedTime` (`updated_at`) USING BTREE,
  KEY `IXVerifiedHook` (`verified`) USING BTREE,
  KEY `unique_id` (`unique_id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `TransactionItem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `reference_id` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IXTransactionID` (`transaction_id`),
  CONSTRAINT `TransactionID_FKIDX` FOREIGN KEY (`transaction_id`) REFERENCES `Transaction` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=316 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `TransactionMeta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id_2` (`transaction_id`,`key`),
  KEY `transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `TransactionProduct` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_id` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unique_id` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wholesale` decimal(10,2) DEFAULT NULL,
  `retail` decimal(10,2) DEFAULT NULL,
  `shipping` decimal(10,2) DEFAULT NULL,
  `handling` decimal(10,2) DEFAULT NULL,
  `vendor_code` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kg` decimal(4,2) DEFAULT '0.00',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('digital','physical') COLLATE utf8mb4_unicode_ci DEFAULT 'physical',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQTransactionProduct` (`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(80) NOT NULL,
  `lastname` varchar(80) NOT NULL,
  `email_address` varchar(125) NOT NULL,
  `password` varchar(75) NOT NULL,
  `role` varchar(20) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `organization_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invite_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `webhook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `event` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  CONSTRAINT `webhook_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `Organization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
