<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

/**
 * Provides access to settings relevant for services in the data access component.
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettings {

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
	 * @param int $maxSerializedEntitySizeInKiloBytes
	 * @param bool $useSearchFields
	 * @param bool $forceWriteSearchFields
	 */
	public function __construct(
		$maxSerializedEntitySizeInKiloBytes,
		$useSearchFields,
		$forceWriteSearchFields
	) {
		Assert::parameterType( 'integer', $maxSerializedEntitySizeInKiloBytes, '$maxSerializedEntitySizeInBytes' );
		Assert::parameterType( 'boolean', $useSearchFields, '$useSearchFields' );
		Assert::parameterType( 'boolean', $forceWriteSearchFields, '$forceWriteSearchFields' );

		$this->maxSerializedEntitySizeInBytes = $maxSerializedEntitySizeInKiloBytes * 1024;
		$this->useSearchFields = $useSearchFields;
		$this->forceWriteSearchFields = $forceWriteSearchFields;
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

}
