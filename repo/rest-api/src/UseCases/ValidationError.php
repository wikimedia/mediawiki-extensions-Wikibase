<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

/**
 * @license GPL-2.0-or-later
 */
class ValidationError {
	private $value;
	private $source;
	private $message;

	public function __construct( string $value, string $source, string $message ) {
		$this->value = $value;
		$this->source = $source;
		$this->message = $message;
	}

	public function getValue(): string {
		return $this->value;
	}

	public function getSource(): string {
		return $this->source;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
