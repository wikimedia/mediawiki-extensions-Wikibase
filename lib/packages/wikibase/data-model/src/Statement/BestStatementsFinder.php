<?php

namespace Wikibase\DataModel\Statement;

use InvalidArgumentException;
use OutOfBoundsException;
use Traversable;
use Wikibase\DataModel\ByPropertyIdGrouper;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Service class to find the best statements in a list of them.
 *
 * @since 1.1
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
	 * @since 1.1
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
	 * @since 1.1
	 *
	 * @param PropertyId $propertyId
	 * @return Statement[]
	 * @throws OutOfBoundsException
	 */
	public function getBestStatementsForProperty( PropertyId $propertyId ) {
		$bestRank = Statement::RANK_NORMAL;
		$statements = array();

		/** @var Statement $statement */
		foreach ( $this->byPropertyIdGrouper->getByPropertyId( $propertyId ) as $statement ) {
			$rank = $statement->getRank();
			if ( $rank > $bestRank ) {
				// clear statements if we found a better one
				$statements = array();
				$bestRank = $rank;
			}
			if ( $rank === $bestRank ) {
				$statements[] = $statement;
			}
		}

		return $statements;
	}

}
