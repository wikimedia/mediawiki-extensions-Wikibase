<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class MissingFieldException extends SerializationException {

	private string $field;
	private string $path;

	public function __construct( string $field, string $path = '', string $message = '', ?Throwable $previous = null ) {
		$this->field = $field;
		$this->path = $path;
		parent::__construct( $message, 0, $previous );
	}

	public function getField(): string {
		return $this->field;
	}

	public function getPath(): string {
		return $this->path;
	}

}
