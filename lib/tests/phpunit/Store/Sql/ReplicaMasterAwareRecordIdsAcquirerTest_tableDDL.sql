CREATE TABLE /*_*/replica_master_aware_record_ids_acquirer_test (
	id int primary key auto_increment,
	column_value varchar(255) null,
	column_id int null
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/idx_replica_master_aware_record_ids_acquirer_test
ON replica_master_aware_record_ids_acquirer_test ( column_value, column_id );
