<?php

namespace Wikibase\Lib\Store;

/**
 * Constants used for looking up entities
 *
 * @license GPL-2.0-or-later
 * @author toan
 */
final class LookupConstants {

	/**
	 * Flag to use instead of a revision ID to indicate that the latest revision is desired,
	 * but a slightly lagged version is acceptable. This would generally be the case when fetching
	 * entities for display.
	 */
	public const LATEST_FROM_REPLICA = 'replica';

	/**
	 * Flag used to indicate that loading slightly lagged data is fine (like
	 * LATEST_FROM_REPLICA), but in case an entity or revision couldn't be found,
	 * we try loading it from master.
	 *
	 * Note that this flag must only be used in code that is exclusively called from POST requests,
	 * since master may reside in a different datacenter and GET requests which trigger reading or
	 * writing to master result in an error in that case.
	 */
	public const LATEST_FROM_REPLICA_WITH_FALLBACK = 'master_fallback';

	/**
	 * Flag to use instead of a revision ID to indicate that the latest revision is desired,
	 * and it is essential to assert that there really is no newer version, to avoid data loss
	 * or conflicts. This would generally be the case when loading an entity for
	 * editing/modification.
	 */
	public const LATEST_FROM_MASTER = 'master';

}
