<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\Federation\RemoteEntityId;

/**
 * EntityExistsValidator checks that a given entity exists.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityExistsValidator implements ValueValidator {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string|null
	 */
	private $entityType;

	public function __construct( EntityLookup $entityLookup, ?string $entityType = null ) {
		$this->entityLookup = $entityLookup;
		$this->entityType = $entityType;
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

		$actualType = $value->getEntityType();

		$errors = [];

		if ( $this->entityType !== null && $actualType !== $this->entityType ) {
			$errors[] = Error::newError(
				"Wrong entity type: " . $actualType,
				null,
				'bad-entity-type',
				[ $actualType ]
			);
		}

		if ( $value instanceof RemoteEntityId ) {
			// For federated values, local EntityLookup cannot resolve them.
			// We only enforce the entityType constraint above; if that's fine,
			// treat the remote entity as "existing" for the purpose of this validator.
			return !$errors ? Result::newSuccess() : Result::newError( $errors );
		}

		if ( !$this->entityLookup->hasEntity( $value ) ) {
			$errors[] = Error::newError(
				"Entity not found: " . $value,
				null,
				'no-such-entity',
				[ $value ]
			);
		}

		return !$errors ? Result::newSuccess() : Result::newError( $errors );
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
