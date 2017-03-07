<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;

/**
 * Factory of DispatchingEntityInfoBuilders configured for a list of entity IDs.
 * Builders used in the DispatchingEntityInfoBuilder created by the factory
 * are created depending on to which repositories requested entities belong to.
 *
 * @license GPL-2.0+
 */
class DispatchingEntityInfoBuilderFactory implements EntityInfoBuilderFactory {

	/**
	 * @var EntityInfoBuilderFactory[]
	 */
	private $builderFactories;

	/**
	 * @param EntityInfoBuilderFactory[] $builderFactories Associative array mapping repository names to
	 * EntityInfoBuilderFactory objects configured for the given repository.
	 */
	public function __construct( array $builderFactories ) {
		Assert::parameter( $builderFactories !== [], '$builderFactories', 'must not be empty' );
		foreach ( $builderFactories as $repositoryName => $factory ) {
			Assert::parameterType( EntityInfoBuilderFactory::class, $factory, '$factory' );
			RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );
		}

		$this->builderFactories = $builderFactories;
	}

	/**
	 * Returns a new DispatchingEntityInfoBuilder for gathering information about the
	 * Entities specified by the given IDs.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityInfoBuilder
	 */
	public function newEntityInfoBuilder( array $entityIds ) {
		// TODO: should this filter $entityIds and only pass IDs from a given repository
		// to this repository's builder factory?
		// Possibly skipping factories of repositories with no entities related?
		$builders = [];
		foreach ( $this->builderFactories as $repositoryName => $factory ) {
			$builders[$repositoryName] = $factory->newEntityInfoBuilder( $entityIds );
		}

		return new DispatchingEntityInfoBuilder( $builders );
	}

}
