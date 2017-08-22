CREATE TABLE gems__oauth_refresh_tokens (
  id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  access_token_id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  revoked tinyint(1) unsigned not null,
  expires_at DATETIME null,

  PRIMARY KEY (id),
  INDEX (access_token_id)
);