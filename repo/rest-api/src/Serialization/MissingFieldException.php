<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class MissingFieldException extends SerializationException {

	private string $field;

	public function __construct( string $field, string $message = '', Throwable $previous = null ) {
		$this->field = $field;
		parent::__construct( $message, 0, $previous );
	}

	public function getField(): string {
		return $this->field;
	}

}
