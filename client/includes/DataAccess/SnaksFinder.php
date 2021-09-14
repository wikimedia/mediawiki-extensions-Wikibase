<?php

namespace Wikibase\Client\DataAccess;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Find Snaks for claims in a given Entity, based on NumericPropertyId.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class SnaksFinder {

	/**
	 * @param StatementListProvider $statementListProvider
	 * @param NumericPropertyId $propertyId The NumericPropertyId for which we want the formatted Snaks
	 * @param int[]|null $acceptableRanks
	 *
	 * @return Snak[] List of main snaks, all guaranteed to belong to the same property ID.
	 */
	public function findSnaks(
		StatementListProvider $statementListProvider,
		NumericPropertyId $propertyId,
		array $acceptableRanks = null
	) {
		$statementList = $this->getStatementsWithPropertyId( $statementListProvider, $propertyId );
		if ( $acceptableRanks === null ) {
			return $statementList->getBestStatements()->getMainSnaks();
		} else {
			return $statementList->getByRank( $acceptableRanks )->getMainSnaks();
		}
	}

	/**
	 * @param StatementListProvider $statementListProvider
	 * @param NumericPropertyId $propertyId
	 *
	 * @return StatementList
	 */
	private function getStatementsWithPropertyId( StatementListProvider $statementListProvider, NumericPropertyId $propertyId ) {
		return $statementListProvider
			->getStatements()
			->getByPropertyId( $propertyId );
	}

}
