<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikimedia\Assert\Assert;

/**
 * Delegates method calls to EntityInfoBuilder instances configures for a particular repository.
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityInfoBuilder implements EntityInfoBuilder {

	/**
	 * @var EntityInfoBuilder[]
	 */
	private $builders;

	/**
	 * @param EntityInfoBuilder[] $builders Associative array mapping repository names to EntityInfoBuilder
	 * instances configured for the given repository. Empty-string key defines a builder for the local repository.
	 */
	public function __construct( array $builders ) {
		Assert::parameter( $builders !== [], '$builders', 'must not be empty' );
		Assert::parameterElementType( EntityInfoBuilder::class, $builders, '$builders' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $builders, '$builders' );

		$this->builders = $builders;
	}

	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		$info = [];

		foreach ( $this->builders as $builder ) {
			// This assumes that each per-repo EntityInfoBuilder only returns EntityInfo for its own entities.
			// If the EntityInfoBuilder was also returning (maybe partial) information on other repo's entities,
			// this should be adjusted to do a per-entity merge.
			$info = array_merge( $info, $builder->collectEntityInfo( $entityIds, $languageCodes )->asArray() );
		}

		return new EntityInfo( $info );
	}

}
