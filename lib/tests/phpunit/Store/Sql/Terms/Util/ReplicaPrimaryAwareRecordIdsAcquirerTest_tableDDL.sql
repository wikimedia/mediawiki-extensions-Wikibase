CREATE TABLE /*_*/replica_primary_aware_record_ids_acquirer_test(
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  column_value BLOB NOT NULL,
  column_id INTEGER NULL
);

CREATE UNIQUE INDEX idx_replica_primary_aware_record_ids_acquirer_test
ON /*_*/replica_primary_aware_record_ids_acquirer_test ( column_value, column_id );
