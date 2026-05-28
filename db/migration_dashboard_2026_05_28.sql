-- Migration for new dashboard screens (member directory, real-time community
-- dashboard 1-4, sabha schedule events). 2026-05-28.

CREATE TABLE IF NOT EXISTS `quick_notes` (
  `qnid` int(11) NOT NULL AUTO_INCREMENT,
  `yid` int(11) NOT NULL,
  `note` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `ndate` date NOT NULL,
  `isDeleted` int(1) NOT NULL DEFAULT '0',
  `cdt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`qnid`),
  KEY `idx_yid_date` (`yid`, `ndate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_hub` (
  `rhid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` enum('prayer_book','meeting_minute','event','spotlight','member_directory') COLLATE utf8_unicode_ci NOT NULL,
  `file_url` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `img` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `kk_id` int(11) NOT NULL DEFAULT '0',
  `isDeleted` int(1) NOT NULL DEFAULT '0',
  `cdt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rhid`),
  KEY `idx_type` (`type`, `isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
