CREATE TABLE gems__oauth_clients (
  id bigint unsigned not null auto_increment,
  user_id varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  name varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  secret varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  redirect varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL,
  active tinyint(1) unsigned not null DEFAULT '0',
  changed timestamp not null,
  changed_by bigint(20) not null,
  created timestamp not null,
  created_by bigint(20) not null,

  PRIMARY KEY (id)
);