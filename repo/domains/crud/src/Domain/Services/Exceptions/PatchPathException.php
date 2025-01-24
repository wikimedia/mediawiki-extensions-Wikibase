<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services\Exceptions;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class PatchPathException extends Exception {

	private string $field;
	private int $opIndex;

	public function __construct( string $message, string $field, int $opIndex ) {
		parent::__construct( $message );
		$this->field = $field;
		$this->opIndex = $opIndex;
	}

	public function getField(): string {
		return $this->field;
	}

	public function getOpIndex(): int {
		return $this->opIndex;
	}
}
