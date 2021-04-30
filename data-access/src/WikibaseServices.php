<?php

namespace Wikibase\DataAccess;

use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * Interface of the top-level container/factory of data access services.
 *
 * This is made up of DataAccessServices (which are repo or entity source specific)
 * and a few other services that don't currently have their own interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseServices extends DataAccessServices {

	/**
	 * Returns a service that can be registered as a watcher to changes to entity data.
	 * Such watcher gets notified when entity is updated or deleted, or when the entity
	 * redirect is updated.
	 *
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher();

}
