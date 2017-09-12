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