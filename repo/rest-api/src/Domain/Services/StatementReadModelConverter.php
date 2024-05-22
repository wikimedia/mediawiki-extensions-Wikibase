<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use InvalidArgumentException;
use Wikibase\DataModel\Reference as ReferenceWriteModel;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\PredicateProperty;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\RestApi\Domain\ReadModel\Qualifiers;
use Wikibase\Repo\RestApi\Domain\ReadModel\Rank;
use Wikibase\Repo\RestApi\Domain\ReadModel\Reference;
use Wikibase\Repo\RestApi\Domain\ReadModel\References;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
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

	public function convert( StatementWriteModel $inputStatement ): Statement {
		$guid = $inputStatement->getGuid();
		if ( $guid === null ) {
			throw new InvalidArgumentException( 'Can only convert statements that have a non-null GUID' );
		}
		$mainPropertyValuePair = $this->convertSnakToPropertyValuePair( $inputStatement->getMainSnak() );

		return new Statement(
			$this->statementIdParser->parse( $guid ),
			$mainPropertyValuePair->getProperty(),
			$mainPropertyValuePair->getValue(),
			new Rank( $inputStatement->getRank() ),
			$this->convertQualifiers( $inputStatement->getQualifiers() ),
			$this->convertReferences( $inputStatement->getReferences() )
		);
	}

	private function convertQualifiers( SnakList $qualifiers ): Qualifiers {
		return new Qualifiers(
			...array_map(
				[ $this, 'convertSnakToPropertyValuePair' ],
				iterator_to_array( $qualifiers )
			)
		);
	}

	private function convertReferences( ReferenceList $references ): References {
		return new References(
			...array_map(
				fn( ReferenceWriteModel $ref ) => new Reference(
					$ref->getHash(),
					array_map(
						[ $this, 'convertSnakToPropertyValuePair' ],
						iterator_to_array( $ref->getSnaks() )
					)
				),
				iterator_to_array( $references )
			)
		);
	}

	private function convertSnakToPropertyValuePair( Snak $snak ): PropertyValuePair {
		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
		} catch ( PropertyDataTypeLookupException $e ) {
			$dataType = null;
		}

		return new PropertyValuePair(
			new PredicateProperty( $snak->getPropertyId(), $dataType ),
			new Value(
				$snak->getType(),
				$snak instanceof PropertyValueSnak ? $snak->getDataValue() : null
			)
		);
	}

}
