<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;
use Wikibase\StatementRankSerializer;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 */
class GetClaimsStatementFilter implements StatementFilter {

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var array
	 */
	private $requestParams;

	public function __construct( EntityIdParser $idParser, ApiErrorReporter $errorReporter, array $requestParams ) {
		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->requestParams = $requestParams;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return boolean
	 */
	public function statementMatches( Statement $statement ) {
		return $this->rankMatchesFilter( $statement->getRank() )
			&& $this->propertyMatchesFilter( $statement->getPropertyId() );
	}

	private function rankMatchesFilter( $rank ) {
		if ( $rank === null ) {
			return true;
		}

		if ( isset( $this->requestParams['rank'] ) ) {
			$statementRankSerializer = new StatementRankSerializer();
			$unserializedRank = $statementRankSerializer->deserialize( $this->requestParams['rank'] );
			return $rank === $unserializedRank;
		}

		return true;
	}

	private function propertyMatchesFilter( EntityId $propertyId ) {
		if ( isset( $this->requestParams['property'] ) ) {
			try {
				$parsedProperty = $this->idParser->parse( $this->requestParams['property'] );
			} catch ( EntityIdParsingException $e ) {
				$this->errorReporter->dieException( $e, 'param-invalid' );
			}

			/** @var EntityId $parsedProperty */
			return $propertyId->equals( $parsedProperty );
		}

		return true;
	}

}
