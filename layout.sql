SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


-- --------------------------------------------------------

-- Auth Framework

CREATE TABLE IF NOT EXISTS `session` (
`sid` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(25) NOT NULL,
  `suid` int(11) NOT NULL,
  `cid` text NOT NULL,
  `void` int(11) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
`uid` int(11) NOT NULL AUTO_INCREMENT,
  `uname` varchar(25) NOT NULL,
  `password` char(128) NOT NULL,
  `otp` text NOT NULL,
  `admin` int(11) NOT NULL,
  `usecret` text NOT NULL,
  `clid` bigint(20) DEFAULT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

ALTER TABLE `session`
 ADD PRIMARY KEY (`sid`);

ALTER TABLE `users`
 ADD PRIMARY KEY (`uid`), ADD UNIQUE KEY `uname` (`uname`), ADD UNIQUE KEY `clid` (`clid`);

-- -------------------------------------------------------

-- Vortragsprojekt

CREATE TABLE IF NOT EXISTS `slots` (
`slid` int(11) NOT NULL AUTO_INCREMENT,
  `sltime` varchar(50) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `slots`
 ADD PRIMARY KEY (`slid`);

CREATE TABLE IF NOT EXISTS `vcon` (
`coid` int(11) NOT NULL AUTO_INCREMENT,
  `couid` int(11) NOT NULL,
  `covid` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `vcon`
 ADD PRIMARY KEY (`coid`);

CREATE TABLE IF NOT EXISTS `vortrag` (
`vid` int(11) NOT NULL AUTO_INCREMENT,
  `vname` varchar(100) NOT NULL,
  `vslid` int(11) NOT NULL,
  `limit` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `vortrag`
 ADD PRIMARY KEY (`vid`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
