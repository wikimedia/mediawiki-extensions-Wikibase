<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Constructs EntityId objects from entity type identifiers and unique entity ID serialization
 * fragments. A fragment is typically the unique, numeric part of an entity ID, excluding the
 * prefix. Items and properties are always supported for legacy reasons.
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
class FragmentedEntityIdBuilder {

	/**
	 * @var callable[]
	 */
	private $builders;

	/**
	 * @param callable[] $builders Array mapping entity type identifiers to callables accepting a
	 *  single mixed value, representing the unique fragment of an entity ID serialization, and
	 *  returning an EntityId object.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $builders ) {
		foreach ( $builders as $entityType => $builder ) {
			if ( !is_string( $entityType ) || $entityType === '' || !is_callable( $builder ) ) {
				throw new InvalidArgumentException( '$builders must map non-empty strings to callables' );
			}
		}

		$this->builders = $builders;
	}

	/**
	 * @param string $entityType
	 * @param mixed $fragment
	 *
	 * @throws InvalidArgumentException
	 * @return EntityId
	 */
	public function build( $entityType, $fragment ) {
		if ( isset( $this->builders[$entityType] ) ) {
			$id = $this->builders[$entityType]( $fragment );
		} elseif ( $entityType === 'item' ) {
			$id = ItemId::newFromNumber( $fragment );
		} elseif ( $entityType === 'property' ) {
			$id = PropertyId::newFromNumber( $fragment );
		} else {
			throw new InvalidArgumentException( 'Unknown entity type ' . $entityType );
		}

		if ( !( $id instanceof EntityId ) ) {
			throw new InvalidArgumentException( 'Builder for ' . $entityType . ' is invalid' );
		}

		return $id;
	}

}
