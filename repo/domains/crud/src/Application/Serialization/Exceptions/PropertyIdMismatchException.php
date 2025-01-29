<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions;

/**
 * @license GPL-2.0-or-later
 */
class PropertyIdMismatchException extends SerializationException {

	private string $propertyIdKey;
	private string $path;

	/** @var mixed */
	private $propertyIdValue;

	/**
	 * @param string $propertyIdKey
	 * @param mixed $propertyIdValue
	 * @param string $path
	 */
	public function __construct( string $propertyIdKey, $propertyIdValue, string $path ) {
		parent::__construct();
		$this->propertyIdKey = $propertyIdKey;
		$this->propertyIdValue = $propertyIdValue;
		$this->path = $path;
	}

	public function getPropertyIdKey(): string {
		return $this->propertyIdKey;
	}

	/**
	 * @return mixed
	 */
	public function getPropertyIdValue() {
		return $this->propertyIdValue;
	}

	public function getPath(): string {
		return $this->path;
	}

}
