--
-- ������ ������������ Devart dbForge Studio for MySQL, ������ 6.3.341.0
-- �������� �������� ��������: http://www.devart.com/ru/dbforge/mysql/studio
-- ���� �������: 15.06.2015 16:38:35
-- ������ �������: 5.5.23
-- ������ �������: 4.1
--


-- 
-- ���������� ������� ������
-- 
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- 
-- ���������� ����� SQL (SQL mode)
-- 
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 
-- ��������� ���� ������ �� ���������
--
USE form;

--
-- �������� ��� ������� msgs
--
DROP TABLE IF EXISTS msgs;
CREATE TABLE msgs (
  id INT(11) NOT NULL AUTO_INCREMENT,
  email VARCHAR(50) DEFAULT NULL COMMENT 'email ',
  subject VARCHAR(255) DEFAULT NULL,
  message VARCHAR(255) DEFAULT NULL,
  file VARCHAR(255) DEFAULT NULL,
  fileResize VARCHAR(255) DEFAULT NULL,
  data VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB
AUTO_INCREMENT = 201
AVG_ROW_LENGTH = 16384
CHARACTER SET utf8
COLLATE utf8_general_ci;

-- 
-- ����� ������ ��� ������� msgs
--
INSERT INTO msgs VALUES
(200, 'vasiliys492@gmail.com', '4c4rc', 'fghfgh', 'files/557e85e0a2fe71.png', 'files/result/557e85e0bd6c1resize.png', '06/18/2015');

-- 
-- ������������ ���������� ����� SQL (SQL mode)
-- 
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;

-- 
-- ��������� ������� ������
-- 
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;