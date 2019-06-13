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

	const PROPERTY_TERMS_NORMALIZED = true;
	const PROPERTY_TERMS_UNNORMALIZED = false;

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
	 * @var bool
	 */
	private $useNormalizedPropertyTerms;

	/**
	 * @param int $maxSerializedEntitySizeInKiloBytes
	 * @param bool $useSearchFields
	 * @param bool $forceWriteSearchFields
	 * @param bool $useEntitySourceBasedFederation TODO: Temporary. Remove once it is the only federation implementation
	 * @param bool $useNormalizedPropertyTerms TODO: Temporary
	 */
	public function __construct(
		$maxSerializedEntitySizeInKiloBytes,
		$useSearchFields,
		$forceWriteSearchFields,
		$useEntitySourceBasedFederation,
		$useNormalizedPropertyTerms
	) {
		Assert::parameterType( 'integer', $maxSerializedEntitySizeInKiloBytes, '$maxSerializedEntitySizeInBytes' );
		Assert::parameterType( 'boolean', $useSearchFields, '$useSearchFields' );
		Assert::parameterType( 'boolean', $forceWriteSearchFields, '$forceWriteSearchFields' );
		Assert::parameterType( 'boolean', $useEntitySourceBasedFederation, '$useEntitySourceBasedFederation' );
		Assert::parameterType( 'boolean', $useNormalizedPropertyTerms, '$useNormalizedPropertyTerms' );

		$this->maxSerializedEntitySizeInBytes = $maxSerializedEntitySizeInKiloBytes * 1024;
		$this->useSearchFields = $useSearchFields;
		$this->forceWriteSearchFields = $forceWriteSearchFields;
		$this->useEntitySourceBasedFederation = $useEntitySourceBasedFederation;
		$this->useNormalizedPropertyTerms = $useNormalizedPropertyTerms;
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
	 * Whether to read property terms from the new, normalized schema,
	 * rather than from the old wb_terms table.
	 *
	 * The new schema is accessed through classes like
	 * {@link DatabasePropertyTermStore} and {@link PrefetchingPropertyTermLookup},
	 * the old one through classes like {@link TermSqlIndex}
	 * and {@link BufferingTermLookup}.
	 *
	 * This is a temporary setting used during the transition period.
	 * Eventually, the normalized schema will be the only one supported.
	 *
	 * @return bool
	 */
	public function useNormalizedPropertyTerms() {
		return $this->useNormalizedPropertyTerms;
	}

}
