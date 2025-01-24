<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyRetriever {

	public function getProperty( PropertyId $propertyId ): ?Property;

}
