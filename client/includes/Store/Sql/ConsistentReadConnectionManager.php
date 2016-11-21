<?php

namespace Wikibase\Client\Store\Sql;

use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * Database connection manager.
 *
 * This manages access to master and slave databases. It also manages state that indicates whether
 * the slave databases are possibly outdated after a write operation, and thus the master database
 * should be used for subsequent read operations.
 *
 * @note: Services that access overlapping sets of database tables, or interact with logically
 * related sets of data in the database, should share a ConsistentReadConnectionManager. Services accessing
 * unrelated sets of information may prefer to not share a ConsistentReadConnectionManager, so they can still
 * perform read operations against slave databases after a (unrelated, per the assumption) write
 * operation to the master database. Generally, sharing a ConsistentReadConnectionManager improves consistency
 * (by avoiding race conditions due to replication lag), but can reduce performance (by directing
 * more read operations to the master database server).
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 *
 * @deprecated Please use SessionConsistentConnectionManager from core
 */
class ConsistentReadConnectionManager extends SessionConsistentConnectionManager{
	public function forceMaster() {
		$this->prepareForUpdates();
	}
}
