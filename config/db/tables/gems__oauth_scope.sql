CREATE TABLE gems__oauth_scope (
  id bigint unsigned not null auto_increment,
  name varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  description varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  active tinyint(1) unsigned not null DEFAULT '0',
  changed timestamp not null,
  changed_by bigint(20) not null,
  created timestamp not null,
  created_by bigint(20) not null,

  PRIMARY KEY (id)
);