<?php

namespace Wikibase\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Service class to find the best statements in a list of them.
 *
 * @since 1.1
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BestStatementFinder {

	/**
	 * @var ByPropertyIdArray
	 */
	private $byPropertyIdArray;

	/**
	 * @param ByPropertyIdArray $byPropertyIdArray
	 * @throws InvalidArgumentException
	 */
	public function __construct( ByPropertyIdArray $byPropertyIdArray ) {
		$this->assertAreStatements( $byPropertyIdArray );
		$this->byPropertyIdArray = $byPropertyIdArray;
	}

	private function assertAreStatements( ByPropertyIdArray $byPropertyIdArray ) {
		foreach ( $byPropertyIdArray as $statements ) {
			if ( !( $statements instanceof StatementList ) ) {
				throw new InvalidArgumentException( 'All elements need to be of type Statement' );
			}
		}
	}

	/**
	 * Returns a list of best statements for each property.
	 *
	 * @since 1.1
	 *
	 * @return ByPropertyIdArray
	 */
	public function getBestStatementsPerProperty() {
		$bestStatementsPerProperty = new ByPropertyIdArray();
		foreach ( $this->byPropertyIdArray->getPropertyIds() as $propertyId ) {
			$bestStatements = $this->getBestStatementsForProperty( $propertyId );
			$bestStatementsPerProperty = array_merge( $bestStatementsPerProperty, $bestStatements );
		}
		return $bestStatementsPerProperty;
	}

	/**
	 * Returns a list of best statements for the given property.
	 *
	 * @since 1.1
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return StatementList
	 */
	public function getBestStatementsForProperty( PropertyId $propertyId ) {
		$bestRank = Statement::RANK_NORMAL;
		$statements = new StatementList();
		/** @var Statement $statement */
		foreach ( $this->byPropertyIdArray->getByPropertyId( $propertyId ) as $statement ) {
			$rank = $statement->getRank();
			if ( $rank > $bestRank ) {
				// clear statements if we found a better one
				$statements = new StatementList();
				$bestRank = $rank;
			}
			if ( $rank === $bestRank ) {
				$statements->addStatement( $statement );
			}
		}
		return $statements;
	}

}
