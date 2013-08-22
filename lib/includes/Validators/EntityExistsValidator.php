<?php

namespace Wikibase\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\EntityLookup;

/**
 * EntityExistsValidator checks that a given entity exists.
 *
 * @license GPL 2+
 * @file
 *
 * @author Daniel Kinzler
 *
 * @package Wikibase\Validators
 */
class EntityExistsValidator implements ValueValidator {

	/**
	 * @var EntityLookup
	 */
	protected $lookup;

	/**
	 * @param EntityLookup $lookup
	 */
	public function __construct( EntityLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string|EntityId $value The ID to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $value ) {
		if ( $value instanceof EntityIdValue ) {
			$value = $value->getEntityId();
		}

		if ( !( $value instanceof EntityId ) ) {
			throw new \InvalidArgumentException( "Expected an EntityId object" );
		}

		if ( !$this->lookup->hasEntity( $value ) ) {
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
