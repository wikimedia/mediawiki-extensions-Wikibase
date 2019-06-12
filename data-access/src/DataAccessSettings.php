<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

/**
 * Provides access to settings relevant for services in the data access component.
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettings {

	const USE_ENTITY_SOURCE_BASED_FEDERATION = true;
	const USE_REPOSITORY_PREFIX_BASED_FEDERATION = false;

	/**
	 * @var int
	 */
	private $maxSerializedEntitySizeInBytes;

	/**
	 * @var bool
	 */
	private $useSearchFields;

	/**
	 * @var bool
	 */
	private $forceWriteSearchFields;

	/**
	 * @var bool
	 */
	private $useEntitySourceBasedFederation;

	/**
	 * @param int $maxSerializedEntitySizeInKiloBytes
	 * @param bool $useSearchFields
	 * @param bool $forceWriteSearchFields
	 * @param bool $useEntitySourceBasedFederation TODO: Temporary. Remove once it is the only federation implementation
	 */
	public function __construct(
		$maxSerializedEntitySizeInKiloBytes,
		$useSearchFields,
		$forceWriteSearchFields,
		$useEntitySourceBasedFederation
	) {
		Assert::parameterType( 'integer', $maxSerializedEntitySizeInKiloBytes, '$maxSerializedEntitySizeInBytes' );
		Assert::parameterType( 'boolean', $useSearchFields, '$useSearchFields' );
		Assert::parameterType( 'boolean', $forceWriteSearchFields, '$forceWriteSearchFields' );
		Assert::parameterType( 'boolean', $useEntitySourceBasedFederation, '$useEntitySourceBasedFederation' );

		$this->maxSerializedEntitySizeInBytes = $maxSerializedEntitySizeInKiloBytes * 1024;
		$this->useSearchFields = $useSearchFields;
		$this->forceWriteSearchFields = $forceWriteSearchFields;
		$this->useEntitySourceBasedFederation = $useEntitySourceBasedFederation;
	}

	/**
	 * @return int
	 */
	public function maxSerializedEntitySizeInBytes() {
		return $this->maxSerializedEntitySizeInBytes;
	}

	/**
	 * @return bool
	 */
	public function useSearchFields() {
		return $this->useSearchFields;
	}

	/**
	 * @return bool
	 */
	public function forceWriteSearchFields() {
		return $this->forceWriteSearchFields;
	}

	public function useEntitySourceBasedFederation() {
		return $this->useEntitySourceBasedFederation;
	}

	/**
	 * @return bool
	 */
	public function useNormalizedPropertyTerms() {
		return false; // TODO make configurable
	}

}
