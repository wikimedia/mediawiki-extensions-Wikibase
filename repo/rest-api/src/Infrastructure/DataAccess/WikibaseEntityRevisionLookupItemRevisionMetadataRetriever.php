<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestItemRevisionMetadataResult as MetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupItemRevisionMetadataRetriever implements ItemRevisionMetadataRetriever {

	private EntityRevisionLookup $revisionLookup;

	public function __construct( EntityRevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	public function getLatestRevisionMetadata( ItemId $itemId ): MetadataResult {
		return $this->revisionLookup->getLatestRevisionId( $itemId )
			->onConcreteRevision( fn ( $id, $timestamp ) => MetadataResult::concreteRevision( $id, $timestamp ) )
			->onRedirect( fn ( int $revId, ItemId $redirectTarget ) => MetadataResult::redirect( $redirectTarget ) )
			->onNonexistentEntity( fn () => MetadataResult::itemNotFound() )
			->map();
	}
}
