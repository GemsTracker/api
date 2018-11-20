CREATE TABLE gems__prediction_models
(
  gpm_id bigint unsigned not null auto_increment,
  gpm_source_id varchar(32) NOT NULL,
  gpm_name varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  gpm_id_track bigint(20) unsigned default null,
  gpm_url varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

  gpm_changed timestamp not null,
  gpm_changed_by bigint(20) not null,
  gpm_created timestamp not null,
  gpm_created_by bigint(20) not null,

  PRIMARY KEY (gpm_id )
);