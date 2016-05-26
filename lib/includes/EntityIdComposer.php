<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Constructs EntityId objects from entity type identifiers and unique parts of entity ID
 * serializations. The unique part is typically the numeric part of an entity ID, excluding the
 * static part that's the same for all IDs of that type. Items and properties are always supported
 * for legacy reasons.
 *
 * Meant to be the counterpart for @see Int32EntityId::getNumericId, as well as an extensible
 * replacement for @see LegacyIdInterpreter::newIdFromTypeAndNumber.
 *
 * @todo Move to DataModel Services.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class EntityIdComposer {

	/**
	 * @var callable[]
	 */
	private $composers;

	/**
	 * @param callable[] $composers Array mapping entity type identifiers to callables accepting a
	 *  single mixed value, representing the unique part of an entity ID serialization, and
	 *  returning an EntityId object.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $composers ) {
		foreach ( $composers as $entityType => $composer ) {
			if ( !is_string( $entityType ) || $entityType === '' || !is_callable( $composer ) ) {
				throw new InvalidArgumentException( '$composers must map non-empty strings to callables' );
			}
		}

		$this->composers = $composers;
	}

	/**
	 * @param string $entityType
	 * @param mixed $uniquePart
	 *
	 * @throws InvalidArgumentException when the entity type is not known or the unique part is not
	 *  unique.
	 * @throws UnexpectedValueException when the configured composer did not return an EntityId
	 *  object.
	 * @return EntityId
	 */
	public function composeEntityId( $entityType, $uniquePart ) {
		if ( isset( $this->composers[$entityType] ) ) {
			$id = $this->composers[$entityType]( $uniquePart );
		} elseif ( $entityType === 'item' ) {
			return ItemId::newFromNumber( $uniquePart );
		} elseif ( $entityType === 'property' ) {
			return PropertyId::newFromNumber( $uniquePart );
		} else {
			throw new InvalidArgumentException( 'Unknown entity type ' . $entityType );
		}

		if ( !( $id instanceof EntityId ) ) {
			throw new UnexpectedValueException( 'Composer for ' . $entityType . ' is invalid' );
		}

		return $id;
	}

}
