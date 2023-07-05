<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyStatementsRetriever {

	public function getStatements( PropertyId $propertyId, ?PropertyId $filterPropertyId = null ): ?StatementList;

}
