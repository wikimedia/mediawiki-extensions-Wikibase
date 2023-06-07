<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestPropertyRevisionMetadataResult as MetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever implements PropertyRevisionMetadataRetriever {

	private EntityRevisionLookup $revisionLookup;

	public function __construct( EntityRevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	public function getLatestRevisionMetadata( NumericPropertyId $propertyId ): MetadataResult {
		return $this->revisionLookup->getLatestRevisionId( $propertyId )
			->onConcreteRevision( fn( $id, $timestamp ) => MetadataResult::concreteRevision( $id, $timestamp ) )
			->onRedirect(
				/** @return never */
				function (): void {
					throw new LogicException( 'Properties cannot be redirected' );
				}
			)->onNonexistentEntity( fn() => MetadataResult::propertyNotFound() )
			->map();
	}
}
