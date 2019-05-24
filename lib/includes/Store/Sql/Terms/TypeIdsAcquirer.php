<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Acquires unique constant ids for stored types.
 *
 * @license GPL-2.0-or-later
 */
interface TypeIdsAcquirer {

	/**
	 * Acquires ids of stored types, persisting the ones that do not exist yet.
	 *
	 * @param array $types list of types to acquire ids for
	 *
	 * @return array keys in returned array are the types passed in in $types
	 *	associated with their acquired ids.
	 *	Example:
	 *		$typeIdsAcquirer->acquireTypeIds ( [ 'label', 'description' ] ) would return:
	 *		[ 'label' => 100, 'description' => 'b48a96cd-c644-4230-811f-cc152dac8455' ]
	 */
	public function acquireTypeIds( array $types ): array;

}
