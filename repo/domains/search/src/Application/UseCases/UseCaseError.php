<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases;

use LogicException;
use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends RuntimeException {

	public const INVALID_QUERY_PARAMETER = 'invalid-query-parameter';

	public const CONTEXT_PARAMETER = 'parameter';

	public const EXPECTED_CONTEXT_KEYS = [
		self::INVALID_QUERY_PARAMETER => [ self::CONTEXT_PARAMETER ],
	];

	private string $errorCode;
	private string $errorMessage;
	private array $errorContext;

	public function __construct( string $code, string $message, array $context = [] ) {
		parent::__construct();
		$this->errorCode = $code;
		$this->errorMessage = $message;
		$this->errorContext = $context;

		if ( !array_key_exists( $code, self::EXPECTED_CONTEXT_KEYS ) ) {
			throw new LogicException( "Unknown error code: '$code'" );
		}

		$contextKeys = array_keys( $context );
		$unexpectedContext = array_values( array_diff(
			$contextKeys,
			self::EXPECTED_CONTEXT_KEYS[$code]
		) );
		if ( $unexpectedContext ) {
			throw new LogicException( "Error context for '$code' should not contain keys: " . json_encode( $unexpectedContext ) );
		}
		$missingContext = array_values( array_diff( self::EXPECTED_CONTEXT_KEYS[$code], $contextKeys ) );
		if ( $missingContext ) {
			throw new LogicException( "Error context for '$code' should contain keys: " . json_encode( $missingContext ) );
		}
	}

	public function getErrorCode(): string {
		return $this->errorCode;
	}

	public function getErrorMessage(): string {
		return $this->errorMessage;
	}

	public function getErrorContext(): array {
		return $this->errorContext;
	}

}
