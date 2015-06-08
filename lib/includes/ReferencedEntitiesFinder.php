<?php

namespace Wikibase;

use DataValues\DataValue;
use DataValues\QuantityValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * Finds linked entities given a list of entities or a list of claims.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedEntitiesFinder {

	/**
	 * @var EntityIdParser
	 */
	private $externalEntityIdParser;

	/**
	 * @param EntityIdParser $externalEntityIdParser Parser for external entity IDs (usually URIs)
	 *        into EntityIds. Such external entity IDs are used for units in QuantityValues, for
	 *        calendar models in TimeValue, and for the reference globe in GlobeCoordinateValues.
	 */
	public function __construct( EntityIdParser $externalEntityIdParser ) {
		$this->externalEntityIdParser = $externalEntityIdParser;
	}

	/**
	 * Finds linked entities within a set of snaks.
	 *
	 * @since 0.4
	 *
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[] Entity id strings pointing to EntityId objects.
	 */
	public function findSnakLinks( array $snaks ) {
		$entityIds = array();

		foreach ( $snaks as $snak ) {
			$propertyId = $snak->getPropertyId();
			$entityIds[$propertyId->getSerialization()] = $propertyId;

			if ( $snak instanceof PropertyValueSnak ) {
				$dataValue = $snak->getDataValue();
				$this->addEntityIdsFromValue( $dataValue, $entityIds );
			}
		}

		return $entityIds;
	}

	/**
	 * @param DataValue $dataValue
	 * @param EntityId[] $entityIds
	 */
	private function addEntityIdsFromValue( DataValue $dataValue, array &$entityIds ) {
		if ( $dataValue instanceof EntityIdValue ) {
			$entityId = $dataValue->getEntityId();
			$entityIds[$entityId->getSerialization()] = $entityId;
		} elseif ( $dataValue instanceof QuantityValue ) {
			$unitUri = $dataValue->getUnit();
			$this->addEntityIdsFromURI( $unitUri, $entityIds );
		}

		//TODO: EntityIds from GlobeCoordinateValue's globe URI (Wikidata, not local item URI!)
		//TODO: EntityIds from TimeValue's calendar URI (Wikidata, not local item URI!)
	}

	/**
	 * @param string $uri
	 * @param EntityId[] $entityIds
	 */
	private function addEntityIdsFromURI( $uri, array &$entityIds ) {
		try {
			$entityId = $this->externalEntityIdParser->parse( $uri );
			$entityIds[$entityId->getSerialization()] = $entityId;
		} catch ( EntityIdParsingException $ex ) {
			// noop
		}
	}


}
