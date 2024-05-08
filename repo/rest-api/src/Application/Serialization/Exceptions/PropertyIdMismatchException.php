<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization\Exceptions;

/**
 * @license GPL-2.0-or-later
 */
class PropertyIdMismatchException extends SerializationException {

	private string $propertyIdKey;
	private string $propertyIdValue;
	private string $path;

	public function __construct( string $propertyIdKey, string $propertyIdValue, string $path ) {
		parent::__construct();
		$this->propertyIdKey = $propertyIdKey;
		$this->propertyIdValue = $propertyIdValue;
		$this->path = $path;
	}

	public function getPropertyIdKey(): string {
		return $this->propertyIdKey;
	}

	public function getPropertyIdValue(): string {
		return $this->propertyIdValue;
	}

	public function getPath(): string {
		return $this->path;
	}

}
