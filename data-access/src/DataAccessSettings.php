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
	 * @param int $maxSerializedEntitySizeInKiloBytes
	 */
	public function __construct( $maxSerializedEntitySizeInKiloBytes ) {
		Assert::parameterType( 'integer', $maxSerializedEntitySizeInKiloBytes, '$maxSerializedEntitySizeInBytes' );

		$this->maxSerializedEntitySizeInBytes = $maxSerializedEntitySizeInKiloBytes * 1024;
	}

	/**
	 * @return int
	 */
	public function maxSerializedEntitySizeInBytes() {
		return $this->maxSerializedEntitySizeInBytes;
	}

}
