<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;

/**
 * Delegates method calls to EntityInfoBuilder instances configures for a particular repository.
 *
 * @license GPL-2.0+
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

	/**
	 * @see EntityInfoBuilder::getEntityInfo
	 *
	 * @return EntityInfo
	 */
	public function getEntityInfo() {
		$info = [];

		foreach ( $this->builders as $builder ) {
			// This assumes that each per-repo EntityInfoBuilder only returns EntityInfo for its own entities.
			// If the EntityInfoBuilder was also returning (maybe partial) information on other repo's entities,
			// this should be adjusted to do a per-entity merge.
			$info = array_merge( $info, $builder->getEntityInfo()->asArray() );
		}

		return new EntityInfo( $info );
	}

	/**
	 * @see EntityInfoBuilder::resolveRedirects
	 */
	public function resolveRedirects() {
		foreach ( $this->builders as $builder ) {
			$builder->resolveRedirects();
		}
	}

	/**
	 * @see EntityInfoBuilder::collectTerms
	 *
	 * @param string[]|null $termTypes Which types of terms to include
	 * @param string[]|null $languages Which languages to include
	 */
	public function collectTerms( array $termTypes = null, array $languages = null ) {
		foreach ( $this->builders as $builder ) {
			$builder->collectTerms( $termTypes, $languages );
		}
	}

	/**
	 * @see EntityInfoBuilder::collectDataTypes
	 */
	public function collectDataTypes() {
		foreach ( $this->builders as $builder ) {
			$builder->collectDataTypes();
		}
	}

	/**
	 * @see EntityInfoBuilder::removeMissing
	 *
	 * @param string $redirects A string flag indicating whether redirects
	 *        should be kept or removed. Must be either 'keep-redirects'
	 *        or 'remove-redirects'.
	 */
	public function removeMissing( $redirects = 'keep-redirects' ) {
		foreach ( $this->builders as $builder ) {
			$builder->removeMissing( $redirects );
		}
	}

	/**
	 * @see EntityInfoBuilder::removeEntityInfo
	 *
	 * @param EntityId[] $ids
	 */
	public function removeEntityInfo( array $ids ) {
		$idsPerRepo = $this->groupEntityIdsByRepository( $ids );

		foreach ( $idsPerRepo as $repositoryName => $repositoryIds ) {
			$builder = $this->getBuilderForRepository( $repositoryName );
			if ( $builder !== null ) {
				$builder->removeEntityInfo( $repositoryIds );
			}
		}
	}

	/**
	 * @see EntityInfoBuilder::retainEntityInfo
	 *
	 * @param EntityId[] $ids
	 */
	public function retainEntityInfo( array $ids ) {
		$idsPerRepo = $this->groupEntityIdsByRepository( $ids );

		foreach ( $idsPerRepo as $repositoryName => $repositoryIds ) {
			$builder = $this->getBuilderForRepository( $repositoryName );
			if ( $builder !== null ) {
				$builder->retainEntityInfo( $repositoryIds );
			}
		}
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return array[] Associative array mapping repository names to list of EntityIds from $ids
	 * that belong to the particular repository.
	 */
	private function groupEntityIdsByRepository( array $ids ) {
		$idsPerRepo = [];

		foreach ( $ids as $id ) {
			$idsPerRepo[$id->getRepositoryName()][] = $id;
		}

		return $idsPerRepo;
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return EntityInfoBuilder|null
	 */
	private function getBuilderForRepository( $repositoryName ) {
		return isset( $this->builders[$repositoryName] ) ? $this->builders[$repositoryName] : null;
	}

}
