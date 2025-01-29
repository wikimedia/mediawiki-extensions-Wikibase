<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions;

use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class InvalidSitelinkBadgeException extends SerializationException {

	/** @var mixed */
	private $value;

	/**
	 * @param mixed $value
	 * @param string $message
	 * @param Throwable|null $previous
	 */
	public function __construct( $value, string $message = '', ?Throwable $previous = null ) {
		$this->value = $value;

		parent::__construct( $message, 0, $previous );
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

}
