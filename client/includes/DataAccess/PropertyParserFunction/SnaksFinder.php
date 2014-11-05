<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use InvalidArgumentException;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\PropertyLabelResolver;

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
		$claims = $this->getClaimsForProperty( $entity, $propertyId, $languageCode );

		$bestClaims = $claims->getBestClaims();

		if ( $bestClaims->isEmpty() ) {
			wfDebugLog( __CLASS__, __METHOD__ . ': no claims found.' );
			wfProfileOut( __METHOD__ );
			return array();
		}

		$snaks = $bestClaims->getMainSnaks();

		wfProfileOut( __METHOD__ );
		return $snaks;
	}

	/**
	 * Returns such Claims from $entity that have a main Snak for the property that
	 * is specified by $propertyId.
	 *
	 * @param Entity $entity The Entity from which to get the clams
	 * @param string $propertyId
	 * @param string $languageCode
	 *
	 * @return Claims The claims for the given property.
	 */
	private function getClaimsForProperty( Entity $entity, $propertyId, $languageCode ) {
		$allClaims = new Claims( $entity->getClaims() );

		return $allClaims->getClaimsForProperty( $propertyId );
	}

}
