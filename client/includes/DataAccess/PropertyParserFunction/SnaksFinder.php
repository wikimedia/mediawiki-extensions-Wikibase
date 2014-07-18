<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use InvalidArgumentException;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\PropertyLabelResolver;

/**
 * Find Snaks for claims in an entity, with EntityId, based on property label or property id.
 *
 * @fixme see what code can be shared with Lua handling code.
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
	private $propertyLabelResolver;

	public function __construct(
		EntityLookup $entityLookup,
		PropertyLabelResolver $propertyLabelResolver
	) {
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabel
	 *
	 * @fixme use SnakList instead of array of Snaks
	 *
	 * @return Snak[]
	 */
	public function findSnaks( EntityId $entityId, $propertyLabel, $languageCode ) {
		wfProfileIn( __METHOD__ );

		$entity = $this->entityLookup->getEntity( $entityId );

		if ( !$entity ) {
			wfDebugLog( __METHOD__, 'Entity not found' );
			wfProfileOut( __METHOD__ );
			return array();
		}

		// We only want the best claims over here, so that we only show the most
		// relevant information.
		$claims = $this->getClaimsForProperty( $entity, $propertyLabel, $languageCode );

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
	 * is specified by $propertyLabel.
	 *
	 * @param Entity $entity The Entity from which to get the clams
	 * @param string $propertyLabel A property label (in the wiki's content language) or a prefixed property ID.
	 * @param string $languageCode
	 *
	 * @return Claims The claims for the given property.
	 */
	private function getClaimsForProperty( Entity $entity, $propertyLabel, $languageCode ) {
		$allClaims = new Claims( $entity->getClaims() );

		$propertyId = $this->getPropertyIdFromIdSerializationOrLabel( $propertyLabel, $languageCode );
		$claims = $allClaims->getClaimsForProperty( $propertyId );

		return $claims;
	}

	/**
	 * @param string $idOrLabel
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 * @throws PropertyLabelNotResolvedException
	 * @return PropertyId
	 */
	private function getPropertyIdFromIdSerializationOrLabel( $idOrLabel, $languageCode ) {
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();

		try {
			$propertyId = $idParser->parse( $idOrLabel );

			if ( !( $propertyId instanceof PropertyId ) ) {
				throw new InvalidArgumentException( 'Not a valid property id' );
			}
		} catch ( EntityIdParsingException $ex ) {
			//XXX: It might become useful to give the PropertyLabelResolver a hint as to which
			//     properties may become relevant during the present request, namely the ones
			//     used by the Item linked to the current page. This could be done with
			//     something like this:
			//
			//     $this->propertyLabelResolver->preloadLabelsFor( $propertiesUsedByItem );

			$propertyIds = $this->propertyLabelResolver->getPropertyIdsForLabels( array( $idOrLabel ) );

			if ( empty( $propertyIds ) ) {
				throw new PropertyLabelNotResolvedException( $idOrLabel, $languageCode );
			}

			$propertyId = $propertyIds[$idOrLabel];
		}

		return $propertyId;
	}

}
