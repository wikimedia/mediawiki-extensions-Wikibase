<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * ItemLookup implementation providing a migration path away from
 * the EntityLookup interface.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyAdapterItemLookup implements ItemLookup {

	private $lookup;

	public function __construct( EntityLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return Item|null
	 * @throws ItemLookupException
	 */
	public function getItemForId( ItemId $itemId ) {
		try {
			return $this->lookup->getEntity( $itemId );
		} catch ( EntityLookupException $ex ) {
			throw new ItemLookupException( $itemId, $ex->getMessage(), $ex );
		}
	}

}
