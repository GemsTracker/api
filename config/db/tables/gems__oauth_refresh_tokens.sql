CREATE TABLE gems__oauth_refresh_tokens (
  refresh_token_id bigint(20) not null auto_increment,
  id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  access_token_id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  revoked tinyint(1) unsigned not null,
  expires_at DATETIME null,

  PRIMARY KEY (refresh_token_id),
  INDEX (id, access_token_id)
);