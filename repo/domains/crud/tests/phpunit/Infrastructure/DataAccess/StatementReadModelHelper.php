<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
trait StatementReadModelHelper {

	private static function newStatementReadModelConverter(): StatementReadModelConverter {
		return new StatementReadModelConverter(
			WikibaseRepo::getStatementGuidParser(),
			new class implements PropertyDataTypeLookup {
				public function getDataTypeIdForProperty( PropertyId $propertyId ): string {
					return 'string';
				}
			}
		);
	}

}
