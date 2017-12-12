CREATE TABLE test_messages (
  id          INTEGER not null ,
  message     varchar(255) not null,
  by          integer not null,
  changed     TEXT not null default current_timestamp,
  changed_by  INTEGER not null,
  created     TEXT not null,
  created_by  INTEGER not null,

  PRIMARY KEY (id)
);

CREATE TABLE gems__oauth_access_tokens (
  id integer not null,
  user_id varchar(255) null,
  client_id varchar(255) not null,
  scopes TEXT NULL,
  revoked integer not null,
  --expires_at DATETIME null,
  expires_at TEXT null,

  PRIMARY KEY (id)
);

CREATE TABLE gems__oauth_auth_codes (
  id integer not null,
  user_id varchar(255) null,
  client_id varchar(255) not null,
  scopes TEXT NULL,
  redirect varchar(255) NULL,
  revoked tinyint(1) not null,
  expires_at DATETIME null,

  PRIMARY KEY (id)
);

CREATE TABLE gems__oauth_clients (
  id integer not null,
  user_id varchar(255) not null,
  name varchar(255) not null,
  secret varchar(255) not null,
  redirect varchar(255) NULL,
  active tinyint(1) not null DEFAULT '0',
  changed timestamp not null,
  changed_by bigint(20) not null,
  created timestamp not null,
  created_by bigint(20) not null,

  PRIMARY KEY (id)
);

CREATE TABLE gems__oauth_refresh_tokens (
  id integer not null,
  access_token_id varchar(100)  not null,
  revoked tinyint(1) not null,
  expires_at DATETIME null,

  PRIMARY KEY (id)
);

CREATE TABLE gems__oauth_scope (
  id bigint not null,
  name varchar(255) not null,
  description varchar(255) not null,
  active tinyint(1) not null DEFAULT '0',
  changed TEXT not null,
  changed_by bigint(20) not null,
  created TEXT not null,
  created_by bigint(20) not null,

  PRIMARY KEY (id)
);

CREATE TABLE gems__organizations (
  gor_id_organization integer not null,
  gor_name varchar(255) not null,
  gor_code varchar(255) not null,

  PRIMARY KEY (gor_id_organization)
);
