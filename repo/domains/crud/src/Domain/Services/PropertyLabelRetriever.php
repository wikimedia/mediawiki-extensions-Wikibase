<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyLabelRetriever {

	public function getLabel( PropertyId $propertyId, string $languageCode ): ?Label;

}
