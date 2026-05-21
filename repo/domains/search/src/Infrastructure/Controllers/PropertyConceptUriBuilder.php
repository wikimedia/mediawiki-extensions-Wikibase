<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyConceptUriBuilder {

	public function __construct(
		private readonly EntitySourceLookup $entitySourceLookup,
	) {
	}

	public function buildConceptUri( PropertyId $propertyId ): string {
		$localId = $propertyId instanceof FederatedPropertyId
			? $propertyId->getRemoteIdSerialization()
			: $propertyId->getSerialization();
		return $this->entitySourceLookup->getEntitySourceById( $propertyId )->getConceptBaseUri()
			. wfUrlencode( $localId );
	}

}
