CREATE TABLE gems__oauth_auth_codes (
  auth_code_id bigint(20) not null auto_increment,
  id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  user_id varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
  client_id varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  scopes TEXT CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL,
  redirect varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL,
  revoked tinyint(1) unsigned not null,
  expires_at DATETIME null,

  PRIMARY KEY (auth_code_id),
  INDEX(id, user_id)
);