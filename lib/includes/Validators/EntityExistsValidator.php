<?php

namespace Wikibase\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\Lib\Store\EntityLookup;

/**
 * EntityExistsValidator checks that a given entity exists.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityExistsValidator implements ValueValidator {

	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param EntityIdValue|EntityId $value The ID to validate
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( $value ) {
		if ( $value instanceof EntityIdValue ) {
			$value = $value->getEntityId();
		}

		if ( !( $value instanceof EntityId ) ) {
			throw new InvalidArgumentException( "Expected an EntityId object" );
		}

		if ( !$this->entityLookup->hasEntity( $value ) ) {
			return Result::newError( array(
				//XXX: we are passing an EntityId as a message parameter here - make sure to turn it into a string later!
				Error::newError( "Entity not found: " . $value, null, 'no-such-entity', array( $value ) ),
			) );
		}

		return Result::newSuccess();
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
