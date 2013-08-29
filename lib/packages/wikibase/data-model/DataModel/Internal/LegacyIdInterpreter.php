<?php

namespace Wikibase\DataModel\Internal;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Turns legacy entity id serializations consisting of entity type + numeric id
 * into present day EntityId implementations.
 *
 * New usages of this class should be very carefully considered.
 * This class is internal to DataModel and should not be used by other components.
 *
 * @since 0.5
 *
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 */
class LegacyIdInterpreter {

	/**
	 * @param string $entityType
	 * @param int|string $numericId
	 *
	 * @return EntityId
	 */
	public static function newIdFromTypeAndNumber( $entityType, $numericId ) {
		$idParser = new BasicEntityIdParser();
		return $idParser->parse( self::constructSerialization( $entityType, $numericId ) );
	}

	/**
	 * Constructs the entity id serialization from entity type and numeric id.
	 *
	 * @param string $entityType
	 * @param int|string $numericId
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected static function constructSerialization( $entityType, $numericId ) {
		$entityTypes = array(
			'item' => 'Q',
			'property' => 'P',
		);

		if ( !array_key_exists( $entityType, $entityTypes ) ) {
			throw new InvalidArgumentException( 'Provided a numeric id (deprecated) for an entity type that never supported this' );
		}

		return $entityTypes[$entityType] . (string)$numericId;
	}

}
