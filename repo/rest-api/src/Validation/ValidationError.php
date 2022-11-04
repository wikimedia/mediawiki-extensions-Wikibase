<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

/**
 * @license GPL-2.0-or-later
 */
class ValidationError {
	private string $source;
	private array $context;

	public function __construct( string $source, array $context = [] ) {
		$this->source = $source;
		$this->context = $context;
	}

	public function getSource(): string {
		return $this->source;
	}

	public function getContext(): array {
		return $this->context;
	}
}
