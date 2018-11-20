CREATE TABLE gems__prediction_model_mapping (

  gpmm_prediction_model_id bigint unsigned not null,
  gpmm_name varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  gpmm_required tinyint(1) unsigned not null DEFAULT '0',

  gpmm_type varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  gpmm_type_id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
  gpmm_type_sub_id varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
  gpmm_custom_mapping text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

  gpmm_changed timestamp not null,
  gpmm_changed_by bigint(20) not null,
  gpmm_created timestamp not null,
  gpmm_created_by bigint(20) not null,

  PRIMARY KEY (gpmm_prediction_model_id, gpmm_name)
);