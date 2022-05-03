<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetaDataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetaDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupItemRevisionMetaDataRetriever implements ItemRevisionMetaDataRetriever {

	private $revisionLookup;

	public function __construct( EntityRevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	public function getLatestRevisionMetaData( ItemId $itemId ): LatestItemRevisionMetaDataResult {
		return $this->revisionLookup->getLatestRevisionId( $itemId )
			->onConcreteRevision( function ( $id, $timestamp ) {
				return LatestItemRevisionMetaDataResult::concreteRevision( $id, $timestamp );
			} )->onRedirect( function ( int $revId, ItemId $redirectTarget ) {
				return LatestItemRevisionMetaDataResult::redirect( $redirectTarget );
			} )->onNonexistentEntity( function () {
				return LatestItemRevisionMetaDataResult::itemNotFound();
			} )->map();
	}
}
