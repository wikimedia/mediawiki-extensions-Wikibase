<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PredicateProperty;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Qualifiers;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Rank;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementReadModelConverter {

	public function __construct(
		private readonly StatementGuidParser $statementIdParser,
		private readonly PropertyDataTypeLookup $dataTypeLookup
	) {
	}

	public function convert( StatementWriteModel $inputStatement ): Statement {
		$guid = $inputStatement->getGuid();
		if ( $guid === null ) {
			throw new InvalidArgumentException( 'Can only convert statements that have a non-null GUID' );
		}

		$mainPropertyValuePair = $this->convertSnakToPropertyValuePair( $inputStatement->getMainSnak() );

		return new Statement(
			$this->statementIdParser->parse( $guid ),
			new Rank( $inputStatement->getRank() ),
			$this->convertQualifiers( $inputStatement->getQualifiers() ),
			$mainPropertyValuePair->property,
		);
	}

	private function convertQualifiers( SnakList $qualifiers ): Qualifiers {
		return new Qualifiers(
			...array_map(
				$this->convertSnakToPropertyValuePair( ... ),
				iterator_to_array( $qualifiers )
			)
		);
	}

	private function convertSnakToPropertyValuePair( Snak $snak ): PropertyValuePair {
		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
		} catch ( PropertyDataTypeLookupException ) {
			$dataType = null;
		}

		return new PropertyValuePair( new PredicateProperty( $snak->getPropertyId(), $dataType ) );
	}

}
