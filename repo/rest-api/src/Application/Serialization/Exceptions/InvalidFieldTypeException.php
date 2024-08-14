<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization\Exceptions;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class InvalidFieldTypeException extends SerializationException {

	private string $field;
	private string $path;

	/** @var mixed */
	private $value;

	/**
	 * @param string $field
	 * @param string $path
	 * @param mixed $value
	 * @param string $message
	 * @param Throwable|null $previous
	 */
	public function __construct( string $field, string $path = '', $value = null, string $message = '', Throwable $previous = null ) {
		$this->field = $field;
		$this->path = $path;
		$this->value = $value;
		parent::__construct( $message, 0, $previous );
	}

	public function getField(): string {
		return $this->field;
	}

	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

}
