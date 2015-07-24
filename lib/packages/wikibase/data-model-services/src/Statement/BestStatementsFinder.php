<?php

namespace Wikibase\DataModel\Services\Statement;

use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Services\ByPropertyIdGrouper;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\PropertyIdProvider;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Service class to find the best statements in a list of them.
 *
 * @since 1.0
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BestStatementsFinder {

	/**
	 * @var ByPropertyIdGrouper
	 */
	private $byPropertyIdGrouper;

	/**
	 * @param Statement[]|Traversable $statements
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $statements ) {
		if ( !( $statements instanceof StatementList ) ) {
			$statements = new StatementList( $statements );
		}

		$this->byPropertyIdGrouper = new ByPropertyIdGrouper( $statements );
	}

	/**
	 * Returns a list of best statements for each property.
	 *
	 * @since 1.0
	 *
	 * @return Statement[]
	 */
	public function getBestStatementsPerProperty() {
		$statements = array();

		foreach ( $this->byPropertyIdGrouper->getPropertyIds() as $propertyId ) {
			$bestStatements = $this->getBestStatementsForProperty( $propertyId );
			$statements = array_merge( $statements, $bestStatements );
		}

		return $statements;
	}

	/**
	 * Returns a list of best statements for the given property.
	 *
	 * @since 1.0
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return Statement[]
	 */
	public function getBestStatementsForProperty( PropertyId $propertyId ) {
		$bestRank = Statement::RANK_NORMAL;
		$statements = array();

		foreach ( $this->getStatementsBy( $propertyId ) as $statement ) {
			if ( $statement instanceof Statement ) {
				$rank = $statement->getRank();

				if ( $rank === $bestRank ) {
					$statements[] = $statement;
				} elseif ( $rank > $bestRank ) {
					$statements = array( $statement );
					$bestRank = $rank;
				}
			}
		}

		return $statements;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return PropertyIdProvider[]
	 */
	private function getStatementsBy( PropertyId $propertyId ) {
		if ( $this->byPropertyIdGrouper->hasPropertyId( $propertyId ) ) {
			return $this->byPropertyIdGrouper->getByPropertyId( $propertyId );
		}

		return array();
	}

}
