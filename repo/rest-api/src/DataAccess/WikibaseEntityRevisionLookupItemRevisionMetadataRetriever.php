<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupItemRevisionMetadataRetriever implements ItemRevisionMetadataRetriever {

	private EntityRevisionLookup $revisionLookup;

	public function __construct( EntityRevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	public function getLatestRevisionMetadata( ItemId $itemId ): LatestItemRevisionMetadataResult {
		return $this->revisionLookup->getLatestRevisionId( $itemId )
			->onConcreteRevision( function ( $id, $timestamp ) {
				return LatestItemRevisionMetadataResult::concreteRevision( $id, $timestamp );
			} )->onRedirect( function ( int $revId, ItemId $redirectTarget ) {
				return LatestItemRevisionMetadataResult::redirect( $redirectTarget );
			} )->onNonexistentEntity( function () {
				return LatestItemRevisionMetadataResult::itemNotFound();
			} )->map();
	}
}
