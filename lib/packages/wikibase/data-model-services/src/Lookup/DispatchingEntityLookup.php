<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Delegates lookup to the repository-specific EntityLookup
 * based on the name of the repository an EntityId belongs to.
 * This class does not strip repository prefixes of incoming
 * entity IDs.
 *
 * @since 3.7
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityLookup implements EntityLookup {

	/**
	 * @var EntityLookup[]
	 */
	private $lookups;

	/**
	 * @since 3.7
	 *
	 * @param EntityLookup[] $lookups associative array with repository names (strings) as keys
	 *                                and EntityLookup objects as values.
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $lookups ) {
		Assert::parameter(
			!empty( $lookups ),
			'$lookups',
			'must not be empty'
		);
		Assert::parameterElementType( EntityLookup::class, $lookups, '$lookups' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $lookups, '$lookups' );
		$this->lookups = $lookups;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @since 3.7
	 *
	 * @param EntityId $entityId
	 *
	 * @return null|EntityDocument
	 * Returns null also when $entityId does not belong to the repository with the configured lookup.
	 *
	 * @throws EntityLookupException
	 */
	public function getEntity( EntityId $entityId ) {
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup !== null ? $lookup->getEntity( $entityId ) : null;
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * @since 3.7
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 * Returns false also when $entityId does not belong to the repository with the configured lookup.
	 *
	 * @throws EntityLookupException
	 */
	public function hasEntity( EntityId $entityId ) {
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup !== null ? $lookup->hasEntity( $entityId ) : false;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return EntityLookup|null
	 */
	private function getLookupForEntityId( EntityId $entityId ) {
		$repo = $entityId->getRepositoryName();
		return isset( $this->lookups[$repo] ) ? $this->lookups[$repo] : null;
	}

}
