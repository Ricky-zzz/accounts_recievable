CREATE DATABASE IF NOT EXISTS src_enterprise
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

CREATE USER IF NOT EXISTS 'src_app'@'localhost' IDENTIFIED BY 'SrcPass1234@';
CREATE USER IF NOT EXISTS 'src_app'@'127.0.0.1' IDENTIFIED BY 'SrcPass1234@';

ALTER USER 'src_app'@'localhost' IDENTIFIED BY 'SrcPass1234@';
ALTER USER 'src_app'@'127.0.0.1' IDENTIFIED BY 'SrcPass1234@';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
ON src_enterprise.* TO 'src_app'@'localhost';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
ON src_enterprise.* TO 'src_app'@'127.0.0.1';

CREATE USER IF NOT EXISTS 'src_backup'@'localhost' IDENTIFIED BY 'BackupPass1234@';
CREATE USER IF NOT EXISTS 'src_backup'@'127.0.0.1' IDENTIFIED BY 'BackupPass1234@';

ALTER USER 'src_backup'@'localhost' IDENTIFIED BY 'BackupPass1234@';
ALTER USER 'src_backup'@'127.0.0.1' IDENTIFIED BY 'BackupPass1234@';

GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER, EVENT
ON src_enterprise.* TO 'src_backup'@'localhost';

GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER, EVENT
ON src_enterprise.* TO 'src_backup'@'127.0.0.1';

FLUSH PRIVILEGES;
