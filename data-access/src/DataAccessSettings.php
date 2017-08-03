<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

class DataAccessSettings {

	/**
	 * @var int
	 */
	private $maxSerializedEntitySizeInBytes;

	/**
	 * @var bool
	 */
	private $readFullEntityIdColumn;

	/**
	 * @param int $maxSerializedEntitySizeInKiloBytes
	 * @param bool $readFullEntityIdColumn
	 */
	public function __construct( $maxSerializedEntitySizeInKiloBytes, $readFullEntityIdColumn ) {
		Assert::parameterType( 'integer', $maxSerializedEntitySizeInKiloBytes, '$maxSerializedEntitySizeInBytes' );
		Assert::parameterType( 'boolean', $readFullEntityIdColumn, '$readFullEntityIdColumn' );

		$this->maxSerializedEntitySizeInBytes = $maxSerializedEntitySizeInKiloBytes * 1024;
		$this->readFullEntityIdColumn = $readFullEntityIdColumn;
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
	public function readFullEntityIdColumn() {
		return $this->readFullEntityIdColumn;
	}

}
