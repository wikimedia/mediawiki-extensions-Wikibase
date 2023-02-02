<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseException extends RuntimeException {

	private string $errorCode;
	private string $errorMessage;
	private ?array $errorContext;

	public function __construct( string $code, string $message, array $context = null ) {
		parent::__construct();
		$this->errorCode = $code;
		$this->errorMessage = $message;
		$this->errorContext = $context;
	}

	public function getErrorCode(): string {
		return $this->errorCode;
	}

	public function getErrorMessage(): string {
		return $this->errorMessage;
	}

	public function getErrorContext(): ?array {
		return $this->errorContext;
	}
}
