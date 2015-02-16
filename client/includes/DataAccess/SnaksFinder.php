<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Find Snaks for claims in an entity, with EntityId, based on PropertyId.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SnaksFinder {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param EntityId $entityId The item or property that the property is used on
	 * @param PropertyId $propertyId The PropertyId for which we want the formatted Snaks
	 * @param int[]|null $acceptableRanks
	 *
	 * @return Snak[]
	 */
	public function findSnaks( EntityId $entityId, PropertyId $propertyId, $acceptableRanks = null ) {
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( !$entity instanceof StatementListProvider ) {
			return array();
		}

		$statementList = $this->getStatementsWithPropertyId( $entity, $propertyId );
		if ( $acceptableRanks === null ) {
			$snaks = $this->getBestMainSnaks( $statementList );
		} else {
			$snaks = $this->getMainSnaksByRanks( $statementList, $acceptableRanks );
		}

		return $snaks;
	}

	/**
	 * @param StatementListProvider $statementListProvider
	 * @param PropertyId $propertyId
	 *
	 * @return StatementList
	 */
	private function getStatementsWithPropertyId( StatementListProvider $statementListProvider, PropertyId $propertyId ) {
		return $statementListProvider
			->getStatements()
			->getWithPropertyId( $propertyId );
	}

	/**
	 * @param StatementList $statementList
	 *
	 * @return Snak[]
	 */
	private function getBestMainSnaks( StatementList $statementList ) {
		return $statementList->getBestStatements()->getMainSnaks();
	}

	/**
	 * @param StatementList $statementList
	 * @param int[] $acceptableRanks
	 *
	 * @return Snak[]
	 */
	private function getMainSnaksByRanks( StatementList $statementList, array $acceptableRanks ) {
		return $statementList->getWithRank( $acceptableRanks )->getMainSnaks();
	}
}
