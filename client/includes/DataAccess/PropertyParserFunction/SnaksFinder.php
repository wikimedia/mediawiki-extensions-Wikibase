<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\BestStatementsFinder;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Find Snaks for claims in an entity, with EntityId, based on property label or property id.
 *
 * TODO: see what code can be shared with Lua handling code.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class SnaksFinder {

	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param EntityId $entityId - the item or property that the property is used on
	 * @param PropertyId $propertyId - the PropertyId for which we want the formatted Snaks
	 * @param string $languageCode - language to render values
	 *
	 * TODO: use SnakList instead of array of Snaks
	 *
	 * @return Snak[]
	 */
	public function findSnaks( EntityId $entityId, PropertyId $propertyId, $languageCode ) {
		wfProfileIn( __METHOD__ );

		$entity = $this->entityLookup->getEntity( $entityId );

		if ( !$entity ) {
			wfDebugLog( __METHOD__, 'Entity not found' );
			wfProfileOut( __METHOD__ );
			return array();
		}

		// We only want the best claims over here, so that we only show the most
		// relevant information.
		$bestStatements = $this->getBestStatementsForProperty( $entity, $propertyId );

		if ( empty( $bestStatements ) ) {
			wfDebugLog( __CLASS__, __METHOD__ . ': no claims found.' );
			wfProfileOut( __METHOD__ );
			return array();
		}

		$snaks = array_map(
			function( Statement $statement ) {
				return $statement->getMainSnak();
			},
			$bestStatements
		);

		wfProfileOut( __METHOD__ );
		return $snaks;
	}

	/**
	 * Returns such Claims from $entity that have a main Snak for the property that
	 * is specified by $propertyId.
	 *
	 * @param EntityDocument $entity The Entity from which to get the clams
	 * @param PropertyId $propertyId
	 *
	 * @return Statement[]
	 */
	private function getBestStatementsForProperty( EntityDocument $entity, PropertyId $propertyId ) {
		if ( $entity instanceof StatementListProvider ) {
			$bestStatementsFinder = new BestStatementsFinder( $entity->getStatements() );
			return $bestStatementsFinder->getBestStatementsForProperty( $propertyId );
		}

		return array();
	}

}
