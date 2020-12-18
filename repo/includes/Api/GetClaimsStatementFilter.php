<?php

namespace Wikibase\Repo\Api;

use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;
use Wikibase\Repo\StatementRankSerializer;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 * @author Thiemo Kreuz
 */
class GetClaimsStatementFilter implements StatementFilter {

	public const FILTER_TYPE = 'getClaims';

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var string[]
	 */
	private $requestParams;

	/**
	 * @param EntityIdParser $idParser
	 * @param ApiErrorReporter $errorReporter
	 * @param string[] $requestParams
	 */
	public function __construct(
		EntityIdParser $idParser,
		ApiErrorReporter $errorReporter,
		array $requestParams
	) {
		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->requestParams = $requestParams;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function statementMatches( Statement $statement ) {
		return $this->rankMatchesFilter( $statement->getRank() )
			&& $this->propertyMatchesFilter( $statement->getPropertyId() );
	}

	/**
	 * @param int $rank
	 *
	 * @return bool
	 */
	private function rankMatchesFilter( $rank ) {
		if ( isset( $this->requestParams['rank'] ) ) {
			try {
				$serializer = new StatementRankSerializer();
				$deserializedRank = $serializer->deserialize( $this->requestParams['rank'] );
				return $rank === $deserializedRank;
			} catch ( DeserializationException $ex ) {
				$this->errorReporter->dieException( $ex, 'param-invalid' );
			}
		}

		return true;
	}

	private function propertyMatchesFilter( EntityId $propertyId ) {
		if ( isset( $this->requestParams['property'] ) ) {
			try {
				$parsedProperty = $this->idParser->parse( $this->requestParams['property'] );
				return $propertyId->equals( $parsedProperty );
			} catch ( EntityIdParsingException $ex ) {
				$this->errorReporter->dieException( $ex, 'param-invalid' );
			}
		}

		return true;
	}

}
