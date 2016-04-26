<?php

namespace Wikibase;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @deprecated
 * This class makes many assumptions that do not hold, including
 * - all entities can be constructed empty
 * - only Items and Properties exist
 * - all entities can construct themselves from their serialization
 * Not a single method is non-problematic, so you should not use this class at all.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityFactory {

	/**
	 * @var string[] Maps entity types to classes implementing the respective entity.
	 */
	private $typeMap;

	/**
	 * @since 0.5
	 *
	 * @param string[] $typeToClass Maps entity types to classes implementing the respective entity.
	 */
	public function __construct( array $typeToClass ) {
		$this->typeMap = $typeToClass;
	}

	/**
	 * @since 0.3
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException
	 * @return EntityDocument
	 */
	public function newEmpty( $entityType ) {
		if ( !isset( $this->typeMap[$entityType] ) ) {
			throw new OutOfBoundsException( 'Unknown entity type ' . $entityType );
		}

		$class = $this->typeMap[$entityType];

		if ( method_exists( $class, 'newFromType' ) ) {
			return $class::newFromType( '' );
		}

		return new $class();
	}

}
