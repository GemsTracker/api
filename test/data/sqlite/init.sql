CREATE TABLE test_messages (
  id          INTEGER not null ,
  message     varchar(255) not null,
  by          integer not null,
  optional    varchar(255),
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
  gor_active tinyint(1) not null DEFAULT '0',
  gor_add_respondents tinyint(1) not null DEFAULT '0',


  PRIMARY KEY (gor_id_organization)
);

CREATE TABLE gems__respondents (
  grs_id_user                INTEGER not null,
  grs_ssn                    varchar(128) unique,
  grs_iso_lang               char(2) not null default 'nl',
  -- grs_initials_name          varchar(30) ,
  grs_first_name             varchar(30) ,
  grs_surname_prefix         varchar(10) ,
  grs_last_name              varchar(50) ,
  -- grs_partner_surname_prefix varchar(10) ,
  -- grs_partner_last_name      varchar(50) ,
  grs_gender                 char(1) not null default 'U',
  grs_birthday               TEXT,
  grs_address_1              varchar(80) ,
  grs_address_2              varchar(80) ,
  grs_zipcode                varchar(10) ,
  grs_city                   varchar(40) ,
  -- grs_region                 varchar(40) ,
  grs_iso_country            char(2) not null default 'NL',
  grs_phone_1                varchar(25) ,
  grs_phone_2                varchar(25) ,
  -- grs_phone_3                varchar(25) ,
  -- grs_phone_4                varchar(25) ,
  grs_changed                TEXT not null default current_timestamp,
  grs_changed_by             INTEGER not null,
  grs_created                TEXT not null,
  grs_created_by             INTEGER not null,

  PRIMARY KEY(grs_id_user)
);

CREATE TABLE gems__respondent2org (
  gr2o_patient_nr         varchar(15) not null,
  gr2o_id_organization    INTEGER not null,
  gr2o_id_user            INTEGER not null,
  -- gr2o_id_physician       INTEGER,
  -- gr2o_treatment          varchar(200),
  gr2o_email               varchar(100),
  gr2o_mailable           TINYINT(1) not null default 1,
  gr2o_comments           text,
  gr2o_consent            varchar(20) not null default 'Unknown',
  gr2o_reception_code     varchar(20) default 'OK' not null,
  gr2o_opened             TEXT not null default current_timestamp,
  gr2o_opened_by          INTEGER not null,
  gr2o_changed            TEXT not null,
  gr2o_changed_by         INTEGER not null,
  gr2o_created            TEXT not null,
  gr2o_created_by         INTEGER not null,

  PRIMARY KEY (gr2o_patient_nr, gr2o_id_organization),
  UNIQUE (gr2o_id_user, gr2o_id_organization)
);

CREATE TABLE gems__reception_codes (
  grc_id_reception_code varchar(20) not null,
  grc_description       varchar(40) not null,
  grc_success           TINYINT(1) not null default 0,
  grc_for_surveys       tinyint not null default 0,
  grc_redo_survey       tinyint not null default 0,
  grc_for_tracks        TINYINT(1) not null default 0,
  grc_for_respondents   TINYINT(1) not null default 0,
  grc_overwrite_answers TINYINT(1) not null default 0,
  grc_active            TINYINT(1) not null default 1,

  grc_changed    TEXT not null default current_timestamp,
  grc_changed_by INTEGER not null,
  grc_created    TEXT not null,
  grc_created_by INTEGER not null,

  PRIMARY KEY (grc_id_reception_code)
);


CREATE TABLE gems__agenda_diagnoses (
    gad_diagnosis_code  varchar(50) not null,
    gad_description     varchar(250) null default null,
    gad_coding_method   varchar(10) not null default 'DBC',
    gad_code            varchar(40) null default null,
    gad_source          varchar(20) not null default 'manual',
    gad_id_in_source    varchar(40) null default null,
    gad_active          TINYINT(1) not null default 1,
    gad_filter          TINYINT(1) not null default 0,
    gad_changed         TEXT not null default current_timestamp,
    gad_changed_by      INTEGER not null,
    gad_created         TEXT not null,
    gad_created_by      INTEGER not null,

    PRIMARY KEY (gad_diagnosis_code)
);

CREATE TABLE gems__appointments (
        gap_id_appointment      INTEGER not null ,
        gap_id_user             INTEGER not null,
        gap_id_organization     INTEGER not null,

        gap_id_episode          INTEGER,

        gap_source              varchar(20) not null default 'manual',
        gap_id_in_source        varchar(40),
        gap_manual_edit         TINYINT(1) not null default 0,

        gap_code                varchar(1) not null default 'A',
        -- one off A => Ambulatory, E => Emergency, F => Field, H => Home, I => Inpatient, S => Short stay, V => Virtual
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        -- Not implemented
        -- moodCode http://wiki.ihe.net/index.php?title=1.3.6.1.4.1.19376.1.5.3.1.4.14
        -- one of  PRMS Scheduled, ARQ requested but no TEXT, EVN has occurred

        gap_status              varchar(2) not null default 'AC',
        -- one off AB => Aborted, AC => active, CA => Cancelled, CO => completed
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        gap_admission_time      TEXT not null,
        gap_discharge_time      TEXT,

        gap_id_attended_by      INTEGER,
        gap_id_referred_by      INTEGER,
        gap_id_activity         INTEGER,
        gap_id_procedure        INTEGER,
        gap_id_location         INTEGER,
        gap_diagnosis_code      varchar(50),

        gap_subject             varchar(250),
        gap_comment             TEXT,

        gap_changed             TEXT not null default current_timestamp,
        gap_changed_by          INTEGER not null,
        gap_created             TEXT not null,
        gap_created_by          INTEGER not null,

        PRIMARY KEY (gap_id_appointment),
        UNIQUE (gap_id_in_source, gap_id_organization, gap_source)
);

CREATE TABLE gems__episodes_of_care (
        gec_episode_of_care_id      INTEGER not null ,
        gec_id_user                 INTEGER not null,
        gec_id_organization         INTEGER not null,

        gec_source                  varchar(20) not null default 'manual',
        gec_id_in_source            varchar(40),
        gec_manual_edit             TINYINT(1) not null default 0,

        gec_status                  varchar(1) not null default 'A',
        -- one off A => active, C => Cancelled, E => Error, F => Finished, O => Onhold, P => Planned, W => Waitlist
        -- see https://www.hl7.org/fhir/episodeofcare.html

        gec_startdate               TEXT not null,
        gec_enddate                 TEXT,

        gec_id_attended_by          INTEGER,

        gec_subject                 varchar(250),
        gec_comment                 text,

        gec_diagnosis               varchar(250),
        gec_diagnosis_data          text,
        gec_extra_data              text,

        gec_changed                 TEXT not null default current_timestamp,
        gec_changed_by              INTEGER not null,
        gec_created                 TEXT not null,
        gec_created_by              INTEGER not null,

        PRIMARY KEY (gec_episode_of_care_id)
);