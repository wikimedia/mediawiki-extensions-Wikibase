<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class InvalidFieldTypeException extends SerializationException {

	private string $path;

	/** @var mixed */
	private $value;

	/**
	 * @param mixed $value
	 * @param string $path
	 * @param string $message
	 * @param Throwable|null $previous
	 */
	public function __construct( $value, string $path, string $message = '', ?Throwable $previous = null ) {
		$this->path = $path;
		$this->value = $value;
		parent::__construct( $message, 0, $previous );
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
