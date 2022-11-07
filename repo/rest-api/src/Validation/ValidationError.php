<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

/**
 * @license GPL-2.0-or-later
 */
class ValidationError {
	private string $code;
	private array $context;

	public function __construct( string $code, array $context = [] ) {
		$this->code = $code;
		$this->context = $context;
	}

	public function getCode(): string {
		return $this->code;
	}

	public function getContext(): array {
		return $this->context;
	}
}
