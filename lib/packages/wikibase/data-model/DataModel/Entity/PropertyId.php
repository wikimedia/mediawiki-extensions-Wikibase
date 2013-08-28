<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;
use Wikibase\Property;

/**
 * @since 0.5
 *
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyId extends EntityId {

	const PATTERN = '/^p[1-9][0-9]*$/i';

	/**
	 * @param string $idSerialization
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $idSerialization ) {
		$this->assertValidIdFormat( $idSerialization );

		parent::__construct(
			Property::ENTITY_TYPE,
			$idSerialization
		);
	}

	protected function assertValidIdFormat( $idSerialization ) {
		if ( !is_string( $idSerialization ) ) {
			throw new InvalidArgumentException( 'The id serialization needs to be a string.' );
		}

		if ( !preg_match( self::PATTERN, $idSerialization ) ) {
			throw new InvalidArgumentException( 'Invalid PropertyId serialization provided.' );
		}
	}

	/**
	 * Construct a PropertyId given the numeric part of its serialization.
	 *
	 * CAUTION: new usages of this method are discouraged. Typically you
	 * should avoid dealing with just the numeric part, and use the whole
	 * serialization. Not doing so in new code requires special justification.
	 *
	 * @param int $number
	 *
	 * @return PropertyId
	 * @throws InvalidArgumentException
	 */
	public static function newFromNumber( $number ) {
		if ( !is_int( $number ) ) {
			throw new InvalidArgumentException( '$number needs to be a integer' );
		}

		return new self( 'p' . $number );
	}

}
