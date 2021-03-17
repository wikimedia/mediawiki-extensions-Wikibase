<?php

namespace Wikibase\DataAccess;

use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * Interface of the top-level container/factory of data access services.
 *
 * This is made up of DataAccessServices (which are repo or entity source specific),
 * and GenericServices (that doesn't currently have it's own interface)
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseServices extends DataAccessServices {

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup();

	/**
	 * Returns a service that can be registered as a watcher to changes to entity data.
	 * Such watcher gets notified when entity is updated or deleted, or when the entity
	 * redirect is updated.
	 *
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher();

	/**
	 * @return PrefetchingTermLookup
	 */
	public function getPrefetchingTermLookup();

}
