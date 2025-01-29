<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyAliasesRetriever {

	public function getAliases( PropertyId $propertyId ): ?Aliases;

}
