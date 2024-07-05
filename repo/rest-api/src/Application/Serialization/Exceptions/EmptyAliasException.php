<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization\Exceptions;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class EmptyAliasException extends SerializationException {

	private string $language;
	private int $index;

	public function __construct( string $language, int $index, string $message = '', Throwable $previous = null ) {
		parent::__construct( $message, 0, $previous );
		$this->language = $language;
		$this->index = $index;
	}

	public function getLanguage(): string {
		return $this->language;
	}

	public function getIndex(): int {
		return $this->index;
	}

}
