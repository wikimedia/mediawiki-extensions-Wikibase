<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement as DataModelStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\RestApi\Domain\ReadModel\Qualifiers;
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

		return new ReadModelStatement(
			$this->statementIdParser->parse( $inputStatement->getGuid() ),
			new Property(
				$inputStatement->getPropertyId(),
				$this->lookupDataType( $inputStatement->getPropertyId() )
			),
			new Value( $mainSnak->getType(), $mainSnak instanceof PropertyValueSnak ? $mainSnak->getDataValue() : null ),
			new Rank( $inputStatement->getRank() ),
			$this->convertQualifiers( $inputStatement->getQualifiers() ),
			$inputStatement->getReferences()
		);
	}

	private function convertQualifiers( SnakList $qualifiers ): Qualifiers {
		return new Qualifiers(
			...array_map(
				fn( Snak $qualifier ) => new PropertyValuePair(
					new Property(
						$qualifier->getPropertyId(),
						$this->lookupDataType( $qualifier->getPropertyId() )
					),
					new Value(
						$qualifier->getType(),
						$qualifier instanceof PropertyValueSnak ? $qualifier->getDataValue() : null
					)
				),
				iterator_to_array( $qualifiers )
			)
		);
	}

	private function lookupDataType( PropertyId $propertyId ): ?string {
		try {
			return $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( PropertyDataTypeLookupException $e ) {
			return null;
		}
	}
}
