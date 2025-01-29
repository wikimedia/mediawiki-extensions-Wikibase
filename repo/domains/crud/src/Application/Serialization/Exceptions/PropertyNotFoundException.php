<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization\Exceptions;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class PropertyNotFoundException extends SerializationException {
	private string $path;
	private string $value;

	public function __construct( string $value, string $path = '', string $message = '', ?Throwable $previous = null ) {
		$this->value = $value;
		$this->path = $path;

		parent::__construct( $message, 0, $previous );
	}

	public function getValue(): string {
		return $this->value;
	}

	public function getPath(): string {
		return $this->path;
	}
}
