CREATE TABLE gems__api_permissions (
  gapr_id           bigint(20) NOT NULL AUTO_INCREMENT,
  gapr_role         varchar(30) NOT NULL,
  gapr_resource     varchar(50) NOT NULL,
  gapr_permission   varchar(30) NOT NULL,
  gapr_allowed      tinyint(1) NOT NULL DEFAULT '0',

  PRIMARY KEY (gapr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;