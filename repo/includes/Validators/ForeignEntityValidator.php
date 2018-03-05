<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ForeignEntityValidator implements ValueValidator {

	/**
	 * @var array[]
	 */
	private $supportedEntityTypes;

	/**
	 * @param array[] $supportedEntityTypes map of repository names to lists of supported entity types
	 */
	public function __construct( array $supportedEntityTypes ) {
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames(
			$supportedEntityTypes,
			'$supportedEntityTypes'
		);
		Assert::parameterElementType( 'array', $supportedEntityTypes, '$supportedEntityTypes' );

		$this->supportedEntityTypes = $supportedEntityTypes;
	}

	/**
	 * Ensures an entity's repository name is known and
	 * the corresponding repository supports the entity's type.
	 *
	 * @param EntityId|EntityIdValue $id
	 *
	 * @return Result
	 */
	public function validate( $id ) {
		if ( $id instanceof EntityIdValue ) {
			$id = $id->getEntityId();
		}
		Assert::parameterType( EntityId::class, $id, '$id' );

		if ( !$this->isKnownRepositoryName( $id->getRepositoryName() ) ) {
			return Result::newError( [
				Error::newError(
					'Unknown repository name: ' . $id->getRepositoryName(),
					null,
					'unknown-repository-name',
					[ $id ]
				)
			] );
		}

		if ( !$this->supportsEntityTypeFromRepository( $id ) ) {
			return Result::newError( [
				Error::newError(
					'Unsupported entity type: ' . $id->getEntityType()
					. ' for repository ' . $id->getRepositoryName(),
					null,
					'unsupported-entity-type',
					[ $id ]
				)
			] );
		}

		return Result::newSuccess();
	}

	/**
	 * @param string $repository
	 *
	 * @return bool
	 */
	private function isKnownRepositoryName( $repository ) {
		return array_key_exists( $repository, $this->supportedEntityTypes );
	}

	/**
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	private function supportsEntityTypeFromRepository( EntityId $id ) {
		$repository = $id->getRepositoryName();

		return in_array(
			$id->getEntityType(),
			$this->supportedEntityTypes[$repository]
		);
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
