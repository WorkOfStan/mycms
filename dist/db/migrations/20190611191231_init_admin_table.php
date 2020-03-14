<?php


use Phinx\Migration\AbstractMigration;

class InitAdminTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        
        $dump1 = <<<'MYSQLDUMP'
SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `MYCMSPROJECTSPECIFIC_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin` varchar(50) NOT NULL,
  `salt` bigint(20) unsigned NOT NULL,
  `password_hashed` varchar(40) NOT NULL,
  `rights` int(11) NOT NULL,
  `active` enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `MYCMSPROJECTSPECIFIC_admin` (`id`, `admin`, `salt`, `password_hashed`, `rights`, `active`) VALUES
(1, 'john', 141327478, '0a9a3657709db688184b9eae1b86f3466775357a', 2, '1');
MYSQLDUMP;

        $this->execute($dump1);

    }
}
