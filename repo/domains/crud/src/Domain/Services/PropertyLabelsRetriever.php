<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyLabelsRetriever {

	public function getLabels( PropertyId $propertyId ): ?Labels;

}
