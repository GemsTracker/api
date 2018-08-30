CREATE TABLE gems__oauth_access_tokens (
  access_token_id bigint(20) not null auto_increment,
  id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  user_id varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
  client_id varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  scopes TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL,
  revoked tinyint(1) unsigned not null,
  expires_at DATETIME null,
  changed timestamp not null,
  changed_by bigint(20) not null,
  created timestamp not null,
  created_by bigint(20) not null,

  PRIMARY KEY (access_token_id),
  INDEX (id, user_id)
);