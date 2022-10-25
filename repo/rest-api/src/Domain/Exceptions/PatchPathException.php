<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Exceptions;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class PatchPathException extends Exception {

	private array $operation;
	private string $field;

	public function __construct( string $message, array $operation, string $field ) {
		parent::__construct( $message );
		$this->operation = $operation;
		$this->field = $field;
	}

	public function getOperation(): array {
		return $this->operation;
	}

	public function getField(): string {
		return $this->field;
	}

}
