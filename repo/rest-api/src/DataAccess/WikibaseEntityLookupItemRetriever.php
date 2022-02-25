<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemRetriever implements ItemRetriever {

	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	public function getItem( ItemId $itemId ): ?Item {
		/** @var Item $item */
		$item = $this->entityLookup->getEntity( $itemId );
		'@phan-var Item $item';

		return $item;
	}
}
