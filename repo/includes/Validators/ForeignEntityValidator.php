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
	private $foreignRepositorySettings;

	/**
	 * @param array $foreignRepositorySettings
	 */
	public function __construct( array $foreignRepositorySettings ) {
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames(
			$foreignRepositorySettings,
			'$foreignRepositorySettings'
		);
		Assert::parameterElementType( 'array', $foreignRepositorySettings, '$foreignRepositorySettings' );

		$this->foreignRepositorySettings = $foreignRepositorySettings;
	}

	/**
	 * Ensures an entities' repository name is known and
	 * the corresponding repository supports the entities' type.
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

		if ( !$this->hasSupportedEntityType( $entity ) ) {
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
		return in_array( $repository, array_keys( $this->foreignRepositorySettings ) );
	}

	private function hasSupportedEntityType( EntityDocument $entity ) {
		$repository = $entity->getId()->getRepositoryName();

		if ( isset( $this->foreignRepositorySettings[$repository]['supportedEntityTypes'] ) ) {
			return in_array(
				$entity->getType(),
				$this->foreignRepositorySettings[$repository]['supportedEntityTypes']
			);
		}

		return false;
	}

}
