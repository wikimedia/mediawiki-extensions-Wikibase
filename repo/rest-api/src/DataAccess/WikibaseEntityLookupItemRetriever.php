<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemRetriever implements ItemRetriever {

	private $entityRevisionLookup;

	public function __construct( EntityRevisionLookup $entityRevisionLookup ) {
		$this->entityRevisionLookup = $entityRevisionLookup;
	}

	/**
	 * @throws StorageException
	 */
	public function getItem( ItemId $itemId ): ?Item {
		$itemRevision = $this->entityRevisionLookup->getEntityRevision( $itemId );

		/** @var Item $item */
		$item = $itemRevision->getEntity();
		'@phan-var Item $item';

		return $item;
	}
}
