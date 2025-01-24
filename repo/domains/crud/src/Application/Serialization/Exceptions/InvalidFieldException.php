<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class InvalidFieldException extends SerializationException {
	private string $field;
	private string $path;

	/** @var mixed */
	private $value;

	/**
	 * @param string $field
	 * @param mixed $value
	 * @param string $path
	 * @param string $message
	 * @param Throwable|null $previous
	 */
	public function __construct( string $field, $value, string $path = '', string $message = '', ?Throwable $previous = null ) {
		$this->field = $field;
		$this->value = $value;
		$this->path = $path;

		parent::__construct( $message, 0, $previous );
	}

	public function getField(): string {
		return $this->field;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	public function getPath(): string {
		return $this->path;
	}
}
