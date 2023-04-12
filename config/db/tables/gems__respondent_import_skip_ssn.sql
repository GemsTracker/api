
CREATE TABLE if not exists gems__respondent_import_skip_ssn (
    griss_id            bigint unsigned not null auto_increment,
    griss_patient_nr    varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

    PRIMARY KEY (`griss_id`),
    INDEX(griss_patient_nr)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
