<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement as DataModelStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\Rank;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement as ReadModelStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Value;

/**
 * @license GPL-2.0-or-later
 */
class StatementReadModelConverter {

	private StatementGuidParser $statementIdParser;
	private PropertyDataTypeLookup $dataTypeLookup;

	public function __construct( StatementGuidParser $statementIdParser, PropertyDataTypeLookup $dataTypeLookup ) {
		$this->statementIdParser = $statementIdParser;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	public function convert( DataModelStatement $inputStatement ): ReadModelStatement {
		$mainSnak = $inputStatement->getMainSnak();

		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $inputStatement->getPropertyId() );
		} catch ( PropertyDataTypeLookupException $e ) {
			$dataType = null;
		}

		return new ReadModelStatement(
			$this->statementIdParser->parse( $inputStatement->getGuid() ),
			new Property( $inputStatement->getPropertyId(), $dataType ),
			new Value( $mainSnak->getType(), $mainSnak instanceof PropertyValueSnak ? $mainSnak->getDataValue() : null ),
			new Rank( $inputStatement->getRank() ),
			$inputStatement->getQualifiers(),
			$inputStatement->getReferences()
		);
	}

}
