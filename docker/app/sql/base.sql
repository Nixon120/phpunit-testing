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
CREATE DATABASE IF NOT EXISTS `pps`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for autoredemption
-- ----------------------------
DROP TABLE IF EXISTS `autoredemption`;
CREATE TABLE `autoredemption`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `product_sku` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `interval` int(1) NOT NULL COMMENT '1 = schedule, 2 = instant',
  `schedule` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `all_participant` int(1) NOT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `updated_at` datetime(0) NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `program_id`(`program_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for contact
-- ----------------------------
DROP TABLE IF EXISTS `contact`;
CREATE TABLE `contact`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `address2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `zip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `reference_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `reference_id`(`reference_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for domain
-- ----------------------------
DROP TABLE IF EXISTS `domain`;
CREATE TABLE `domain`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `url` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `UQDomainKey`(`organization_id`, `url`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for featuredproduct
-- ----------------------------
DROP TABLE IF EXISTS `featuredproduct`;
CREATE TABLE `featuredproduct`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sku` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `program_id`(`program_id`, `sku`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for layoutrow
-- ----------------------------
DROP TABLE IF EXISTS `layoutrow`;
CREATE TABLE `layoutrow`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `priority` int(2) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `program_id`(`program_id`, `priority`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for layoutrowcard
-- ----------------------------
DROP TABLE IF EXISTS `layoutrowcard`;
CREATE TABLE `layoutrowcard`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `row_id` int(11) NOT NULL,
  `priority` int(2) NOT NULL,
  `size` int(2) NOT NULL,
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `product` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `product_row` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `card_show` int(11) NOT NULL DEFAULT 2,
  `text_markdown` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `row_id`(`row_id`, `priority`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for offlineredemption
-- ----------------------------
DROP TABLE IF EXISTS `offlineredemption`;
CREATE TABLE `offlineredemption`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `skus` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `active` int(1) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `program_id`(`program_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for onetimeautoredemption
-- ----------------------------
DROP TABLE IF EXISTS `onetimeautoredemption`;
CREATE TABLE `onetimeautoredemption`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `product_sku` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `all_participant` int(1) NOT NULL,
  `active` int(1) NOT NULL,
  `redemption_date` date NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `program_id`(`program_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for organization
-- ----------------------------
DROP TABLE IF EXISTS `organization`;
CREATE TABLE `organization`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NULL DEFAULT NULL,
  `name` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `lft` int(11) NULL DEFAULT NULL,
  `rgt` int(11) NULL DEFAULT NULL,
  `lvl` int(11) NULL DEFAULT NULL,
  `active` tinyint(1) NULL DEFAULT NULL,
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `unique_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `company_contact_reference` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `accounts_payable_contact_reference` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_id`(`unique_id`) USING BTREE,
  INDEX `IXOrganization`(`name`) USING BTREE,
  INDEX `company_contact_reference`(`company_contact_reference`) USING BTREE,
  INDEX `accounts_payable_contact_reference`(`accounts_payable_contact_reference`) USING BTREE,
  CONSTRAINT `Organization_ibfk_1` FOREIGN KEY (`company_contact_reference`) REFERENCES `contact` (`reference_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `Organization_ibfk_2` FOREIGN KEY (`accounts_payable_contact_reference`) REFERENCES `contact` (`reference_id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for phinxlog
-- ----------------------------
DROP TABLE IF EXISTS `phinxlog`;
CREATE TABLE `phinxlog`  (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `start_time` timestamp(0) NULL DEFAULT NULL,
  `end_time` timestamp(0) NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for productcriteria
-- ----------------------------
DROP TABLE IF EXISTS `productcriteria`;
CREATE TABLE `productcriteria`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `filter` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `featured_page_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `program_id`(`program_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for program
-- ----------------------------
DROP TABLE IF EXISTS `program`;
CREATE TABLE `program`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NULL DEFAULT NULL,
  `name` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `point` int(11) NULL DEFAULT NULL,
  `address1` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `address2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `city` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `state` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `zip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `domain_id` int(11) NULL DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `active` tinyint(1) NULL DEFAULT 1,
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NULL DEFAULT current_timestamp(0),
  `unique_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `contact_reference` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `invoice_to` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'Top Level Client',
  `deposit_amount` int(11) NOT NULL DEFAULT 0,
  `issue_1099` tinyint(1) NOT NULL DEFAULT 0,
  `employee_payroll_file` tinyint(1) NOT NULL DEFAULT 0,
  `cost_center_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `published` int(1) NOT NULL DEFAULT 0,
  `accounting_contact_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `low_level_deposit` int(11) NULL DEFAULT NULL,
  `start_date` date NULL DEFAULT NULL,
  `end_date` datetime(0) NULL DEFAULT NULL,
  `grace_period` int(11) NULL DEFAULT NULL,
  `timezone` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Phoenix',
  `collect_ssn` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `organization_id`(`organization_id`, `unique_id`) USING BTREE,
  INDEX `IXClientKey`(`name`, `created_at`) USING BTREE,
  INDEX `contact_reference`(`contact_reference`) USING BTREE,
  CONSTRAINT `Program_ibfk_1` FOREIGN KEY (`contact_reference`) REFERENCES `contact` (`reference_id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for programtoprogramtype
-- ----------------------------
DROP TABLE IF EXISTS `programtoprogramtype`;
CREATE TABLE `programtoprogramtype`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `program_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `program_id`(`program_id`) USING BTREE,
  INDEX `program_type_id`(`program_type_id`) USING BTREE,
  CONSTRAINT `programtoprogramtype_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `programtoprogramtype_ibfk_2` FOREIGN KEY (`program_type_id`) REFERENCES `programtype` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for programtype
-- ----------------------------
DROP TABLE IF EXISTS `programtype`;
CREATE TABLE `programtype`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for rangedpricingproduct
-- ----------------------------
DROP TABLE IF EXISTS `rangedpricingproduct`;
CREATE TABLE `rangedpricingproduct`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `min` decimal(10, 2) NOT NULL,
  `max` decimal(10, 2) NOT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `updated_at` datetime(0) NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_id`(`unique_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for report
-- ----------------------------
DROP TABLE IF EXISTS `report`;
CREATE TABLE `report`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `program` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `report` int(1) NOT NULL COMMENT '1 = Enrollment,\n2 = Transaction,\n3 = Redemption,\n4 = Participant Summary,\n5 = Point Balance,\n6 = Sweepstake Drawing',
  `format` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `attachment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `parameters` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `result_count` int(11) NOT NULL DEFAULT 0,
  `user` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `report_date` date NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `report`(`report`) USING BTREE,
  INDEX `processed`(`processed`) USING BTREE,
  INDEX `organization`(`organization`) USING BTREE,
  INDEX `program`(`program`) USING BTREE,
  INDEX `user`(`user`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sftp
-- ----------------------------
DROP TABLE IF EXISTS `sftp`;
CREATE TABLE `sftp`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `port` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `key` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  CONSTRAINT `sftp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for participant
-- ----------------------------
DROP TABLE IF EXISTS `participant`;
CREATE TABLE `participant`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NULL DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `email_address` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `password` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `unique_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `sso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `credit` decimal(10, 2) NULL DEFAULT 0,
  `firstname` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `lastname` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `phone` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `address_reference` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `birthdate` date NULL DEFAULT NULL,
  `deactivated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `IXLogin`(`email_address`, `password`) USING BTREE,
  INDEX `IXClientID`(`program_id`) USING BTREE,
  INDEX `IXUniqueID`(`unique_id`) USING BTREE,
  INDEX `IXName`(`firstname`, `lastname`) USING BTREE,
  CONSTRAINT `participant_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for address
-- ----------------------------
DROP TABLE IF EXISTS `address`;
CREATE TABLE `address`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `reference_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address1` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address2` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `city` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `zip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `country` int(11) NOT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `UQShipping`(`reference_id`, `participant_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for adjustment
-- ----------------------------
DROP TABLE IF EXISTS `adjustment`;
CREATE TABLE `adjustment`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `amount` decimal(15, 5) NOT NULL,
  `type` int(1) NOT NULL,
  `active` int(1) NOT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  `transaction_id` int(11) NULL DEFAULT NULL,
  `reference` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `completed_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`participant_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for participant_meta_key
-- ----------------------------
DROP TABLE IF EXISTS `participant_meta_key`;
CREATE TABLE `participant_meta_key`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `participantMetaKeyName`(`keyName`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for participant_meta_value
-- ----------------------------
DROP TABLE IF EXISTS `participant_meta_value`;
CREATE TABLE `participant_meta_value`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `key_id` int(11) NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `UQIndexUserMetaKey`(`participant_id`, `key_id`) USING BTREE,
  INDEX `IndexParticipantMetaKeyId`(`key_id`) USING BTREE,
  INDEX `IndexParticipantMetaParticipantId`(`participant_id`) USING BTREE,
  CONSTRAINT `FK_MetaKeyId` FOREIGN KEY (`key_id`) REFERENCES `participant_meta_key` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_MetaParticipantId` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for shipment
-- ----------------------------
DROP TABLE IF EXISTS `shipment`;
CREATE TABLE `shipment`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NULL DEFAULT NULL,
  `transaction_item_id` int(11) NULL DEFAULT NULL,
  `vendor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `modified_time` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `date` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sweepstake
-- ----------------------------
DROP TABLE IF EXISTS `sweepstake`;
CREATE TABLE `sweepstake`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `active` int(1) NOT NULL,
  `start_date` date NULL DEFAULT NULL,
  `end_date` date NULL DEFAULT NULL,
  `max_participant_entry` int(11) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `type` varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'manual',
  `sku` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'SWEEP01',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `program_id`(`program_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sweepstakedraw
-- ----------------------------
DROP TABLE IF EXISTS `sweepstakedraw`;
CREATE TABLE `sweepstakedraw`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sweepstake_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `draw_count` int(11) NOT NULL,
  `alt_entry` int(11) NOT NULL DEFAULT 0,
  `processed` int(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `sweepstake_id`(`sweepstake_id`, `date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sweepstakeentry
-- ----------------------------
DROP TABLE IF EXISTS `sweepstakeentry`;
CREATE TABLE `sweepstakeentry`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sweepstake_id` int(11) NOT NULL,
  `sweepstake_draw_id` int(11) NULL DEFAULT NULL,
  `sweepstake_alt_draw_id` int(11) NULL DEFAULT NULL,
  `participant_id` int(11) NOT NULL,
  `point` decimal(10, 2) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sweepstake_draw_id`(`sweepstake_draw_id`) USING BTREE,
  INDEX `participant_id`(`participant_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for transaction
-- ----------------------------
DROP TABLE IF EXISTS `transaction`;
CREATE TABLE `transaction`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `wholesale` decimal(10, 2) NOT NULL DEFAULT 0,
  `subtotal` decimal(10, 2) NOT NULL DEFAULT 0,
  `total` decimal(10, 2) NOT NULL DEFAULT 0,
  `email_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=checkout, 2=credit, 3=debit',
  `verified` tinyint(1) NULL DEFAULT 0 COMMENT '0 = false, 1 = true',
  `bypass_conditions` tinyint(1) NULL DEFAULT 0,
  `completed` tinyint(1) NULL DEFAULT 0,
  `processed` tinyint(1) NULL DEFAULT 1,
  `shipping_reference` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  `unique_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `IXDate`(`created_at`) USING BTREE,
  INDEX `IXUserID`(`participant_id`) USING BTREE,
  INDEX `decimal`(`wholesale`) USING BTREE,
  INDEX `IXModifiedTime`(`updated_at`) USING BTREE,
  INDEX `IXVerifiedHook`(`verified`) USING BTREE,
  INDEX `unique_id`(`unique_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for transaction_item_return
-- ----------------------------
DROP TABLE IF EXISTS `transaction_item_return`;
CREATE TABLE `transaction_item_return`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `transaction_item_id` int(11) NOT NULL,
  `notes` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `complete` int(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  INDEX `transaction_item_id`(`transaction_item_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  CONSTRAINT `transaction_item_return_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transaction` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `transaction_item_return_ibfk_2` FOREIGN KEY (`transaction_item_id`) REFERENCES `transactionitem` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `transaction_item_return_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for transactionitem
-- ----------------------------
DROP TABLE IF EXISTS `transactionitem`;
CREATE TABLE `transactionitem`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `reference_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(2) NOT NULL,
  `guid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `reissue_date` date NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `guid`(`guid`) USING BTREE,
  INDEX `IXTransactionID`(`transaction_id`) USING BTREE,
  CONSTRAINT `TransactionID_FKIDX` FOREIGN KEY (`transaction_id`) REFERENCES `transaction` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for transactionmeta
-- ----------------------------
DROP TABLE IF EXISTS `transactionmeta`;
CREATE TABLE `transactionmeta`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime(0) NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `transaction_id_2`(`transaction_id`, `key`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for transactionproduct
-- ----------------------------
DROP TABLE IF EXISTS `transactionproduct`;
CREATE TABLE `transactionproduct`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unique_id` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `wholesale` decimal(10, 2) NULL DEFAULT NULL,
  `retail` decimal(10, 2) NULL DEFAULT NULL,
  `shipping` decimal(10, 2) NULL DEFAULT NULL,
  `handling` decimal(10, 2) NULL DEFAULT NULL,
  `vendor_code` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `kg` decimal(4, 2) NULL DEFAULT 0,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `terms` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `type` enum('digital','physical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'physical',
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `UQTransactionProduct`(`reference_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `lastname` varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `email_address` varchar(125) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(75) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `role` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `organization_id` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `invite_token` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for webhook
-- ----------------------------
DROP TABLE IF EXISTS `webhook`;
CREATE TABLE `webhook`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `event` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime(0) NULL DEFAULT current_timestamp(0),
  `created_at` datetime(0) NOT NULL DEFAULT current_timestamp(0),
  `immutable` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `organization_id`(`organization_id`) USING BTREE,
  CONSTRAINT `webhook_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
