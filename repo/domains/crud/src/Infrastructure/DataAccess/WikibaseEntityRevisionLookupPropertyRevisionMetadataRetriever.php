<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\LatestPropertyRevisionMetadataResult as MetadataResult;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyRevisionMetadataRetriever;

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
			->onConcreteRevision( MetadataResult::concreteRevision( ... ) )
			->onRedirect(
				/** @return never */
				function (): void {
					throw new LogicException( 'Properties cannot be redirected' );
				}
			)->onNonexistentEntity( MetadataResult::propertyNotFound( ... ) )
			->map();
	}
}
