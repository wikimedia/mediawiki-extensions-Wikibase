<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyPartsRetriever {

	public function getPropertyParts( PropertyId $propertyId, array $fields ): ?PropertyParts;

}
