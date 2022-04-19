<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevisionResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupItemRevisionRetriever implements ItemRevisionRetriever {

	private $entityRevisionLookup;

	public function __construct( EntityRevisionLookup $entityRevisionLookup ) {
		$this->entityRevisionLookup = $entityRevisionLookup;
	}

	/**
	 * @throws StorageException
	 */
	public function getItemRevision( ItemId $itemId ): ItemRevisionResult {
		$entityRevision = $this->entityRevisionLookup->getEntityRevision( $itemId );

		if ( $entityRevision === null ) {
			return ItemRevisionResult::itemNotFound();
		}

		/** @var Item $item */
		$item = $entityRevision->getEntity();
		'@phan-var Item $item';

		return ItemRevisionResult::concreteRevision(
			new ItemRevision( $item, $entityRevision->getTimestamp(), $entityRevision->getRevisionId() )
		);
	}
}
