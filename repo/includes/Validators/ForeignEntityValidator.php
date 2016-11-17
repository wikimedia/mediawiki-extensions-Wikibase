<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class ForeignEntityValidator implements EntityValidator {

	/**
	 * @var array
	 */
	private $supportedEntityTypes;

	/**
	 * @param array $supportedEntityTypes map of repository names to lists of supported entity types
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
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validateEntity( EntityDocument $entity ) {
		if ( !$entity->getId()->isForeign() ) {
			return Result::newSuccess();
		}

		if ( !$this->isKnownRepositoryName( $entity->getId()->getRepositoryName() ) ) {
			return Result::newError( [
				Error::newError(
					'Unknown repository name: ' . $entity->getId()->getRepositoryName(),
					null,
					'unknown-repository-name',
					[ $entity ]
				)
			] );
		}

		if ( !$this->supportsEntityTypeFromRepository( $entity ) ) {
			return Result::newError( [
				Error::newError(
					'Unsupported entity type: ' . $entity->getType()
					. ' for repository ' . $entity->getId()->getRepositoryName(),
					null,
					'unsupported-entity-type',
					[ $entity ]
				)
			] );
		}

		return Result::newSuccess();
	}

	private function isKnownRepositoryName( $repository ) {
		return in_array( $repository, array_keys( $this->supportedEntityTypes ) );
	}

	private function supportsEntityTypeFromRepository( EntityDocument $entity ) {
		$repository = $entity->getId()->getRepositoryName();

		if ( isset( $this->supportedEntityTypes[$repository] ) ) {
			return in_array(
				$entity->getType(),
				$this->supportedEntityTypes[$repository]
			);
		}

		return false;
	}

}
