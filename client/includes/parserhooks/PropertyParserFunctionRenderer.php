<?php

namespace Wikibase;

use InvalidArgumentException;
use Language;
use Status;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Renderer of the {{#property}} parser function.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class PropertyParserFunctionRenderer {

	private $language;
	private $entityLookup;
	private $propertyLabelResolver;
	private $snaksFormatter;

	public function __construct( Language $language,
		EntityLookup $entityLookup, PropertyLabelResolver $propertyLabelResolver,
		SnakFormatter $snaksFormatter ) {
		$this->language = $language;
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
		$this->snaksFormatter = $snaksFormatter;
	}

	/**
	 * Returns such Claims from $entity that have a main Snak for the property that
	 * is specified by $propertyLabel.
	 *
	 * @param Entity $entity The Entity from which to get the clams
	 * @param string $propertyLabel A property label (in the wiki's content language) or a prefixed property ID.
	 *
	 * @return Claims The claims for the given property.
	 */
	private function getClaimsForProperty( Entity $entity, $propertyLabel ) {
		$allClaims = new Claims( $entity->getClaims() );

		$propertyId = $this->getPropertyIdFromIdSerializationOrLabel( $propertyLabel );
		$claims = $allClaims->getClaimsForProperty( $propertyId );

		return $claims;
	}

	/**
	 * @param string $string
	 * @return PropertyId
	 * @throws InvalidArgumentException
	 * @throws PropertyLabelNotResolvedException
	 */
	private function getPropertyIdFromIdSerializationOrLabel( $string ) {
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();

		try {
			$propertyId = $idParser->parse( $string );

			if ( ! ( $propertyId instanceof PropertyId ) ) {
				throw new InvalidArgumentException( 'Not a valid property id' );
			}
		} catch ( EntityIdParsingException $ex ) {
			//XXX: It might become useful to give the PropertyLabelResolver a hint as to which
			//     properties may become relevant during the present request, namely the ones
			//     used by the Item linked to the current page. This could be done with
			//     something like this:
			//
			//     $this->propertyLabelResolver->preloadLabelsFor( $propertiesUsedByItem );

			$propertyIds = $this->propertyLabelResolver->getPropertyIdsForLabels( array( $string ) );

			if ( $propertyIds === null || empty( $propertyIds ) ) {
				throw new PropertyLabelNotResolvedException( $string, $this->language->getCode() );
			}

			$propertyId = $propertyIds[$string];
		}

		return $propertyId;
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string - wikitext format
	 */
	private function formatSnakList( $snaks ) {
		$formattedValues = $this->formatSnaks( $snaks );
		return $this->language->commaList( $formattedValues );
	}

	private function formatSnaks( $snaks ) {
		$strings = array();

		foreach ( $snaks as $snak ) {
			$strings[] = $this->snaksFormatter->formatSnak( $snak );
		}

		return $strings;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabel
	 *
	 * @return Status a status object wrapping a wikitext string
	 */
	public function renderForEntityId( EntityId $entityId, $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		$entity = $this->entityLookup->getEntity( $entityId );

		if ( !$entity ) {
			wfProfileOut( __METHOD__ );
			return Status::newGood( '' );
		}

		// We only want the best claims over here, so that we only show the most
		// relevant information.
		$claims = $this->getClaimsForProperty( $entity, $propertyLabel )->getBestClaims();

		if ( $claims->isEmpty() ) {
			wfProfileOut( __METHOD__ );
			return Status::newGood( '' );
		}

		$snakList = $claims->getMainSnaks();
		$text = $this->formatSnakList( $snakList, $propertyLabel );
		$status = Status::newGood( $text );

		wfProfileOut( __METHOD__ );
		return $status;
	}

}
