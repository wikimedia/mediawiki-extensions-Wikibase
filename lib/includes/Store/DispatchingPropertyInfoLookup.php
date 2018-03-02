<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\DBError;

/**
 * A dispatching PropertyInfoLookup implementation that is able to understand property ID strings
 * prefixed with repository names, and forwards to the service responsible for the repository.
 *
 * @license GPL-2.0-or-later
 */
class DispatchingPropertyInfoLookup implements PropertyInfoLookup {

	/**
	 * @var PropertyInfoLookup[] indexed by repository name
	 */
	private $lookups;

	/**
	 * @param PropertyInfoLookup[] $lookups Map of repository name strings to PropertyInfoLookup
	 *  objects.
	 */
	public function __construct( array $lookups ) {
		Assert::parameter( !empty( $lookups ), '$lookups', 'must not be empty' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $lookups, '$lookups' );
		Assert::parameterElementType( PropertyInfoLookup::class, $lookups, '$lookups' );

		$this->lookups = $lookups;
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 *
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$lookup = $this->getLookupForPropertyId( $propertyId );
		return !is_null( $lookup ) ? $lookup->getPropertyInfo( $propertyId ) : null;
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfoForDataType
	 *
	 * Combines the results of getPropertyInfoForDataType from each of the injected PropertyInfoLookups.
	 *
	 * @param string $dataType
	 *
	 * @return array[]
	 *
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		return array_reduce( $this->lookups, function( array $info, PropertyInfoLookup $lookup ) use ( $dataType ) {
			return array_merge( $info, $lookup->getPropertyInfoForDataType( $dataType ) );
		}, [] );
	}

	/**
	 * @see PropertyInfoLookup::getAllPropertyInfo
	 *
	 * Combines the results of getAllPropertyInfo from each of the injected PropertyInfoLookups.
	 *
	 * @return array[]
	 *
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getAllPropertyInfo() {
		return array_reduce( $this->lookups, function( array $info, PropertyInfoLookup $lookup ) {
			return array_merge( $info, $lookup->getAllPropertyInfo() );
		}, [] );
	}

	/**
	 * @param PropertyId $id
	 *
	 * @return PropertyInfoLookup|null
	 */
	private function getLookupForPropertyId( PropertyId $id ) {
		$repo = $id->getRepositoryName();
		return isset( $this->lookups[$repo] ) ? $this->lookups[$repo] : null;
	}

}
