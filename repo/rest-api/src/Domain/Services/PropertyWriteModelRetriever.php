<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyWriteModelRetriever {

	public function getPropertyWriteModel( PropertyId $propertyId ): ?Property;

}
