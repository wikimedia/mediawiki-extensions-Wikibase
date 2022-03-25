<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemErrorResponse implements ErrorResponse {
	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var string
	 */
	private $message;

	public function __construct( string $code, string $message ) {
		$this->code = $code;
		$this->message = $message;
	}

	public static function newFromValidationError(
		ValidationError $validationError
	): self {
		$errorSource = $validationError->getSource();
		switch ( $errorSource ) {
			case GetItemValidationResult::SOURCE_ITEM_ID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					"Not a valid item ID: " . $validationError->getValue()
				);

			case GetItemValidationResult::SOURCE_FIELDS:
				return new self(
					ErrorResponse::INVALID_FIELD,
					"Not a valid field: " . $validationError->getValue()
				);

			default:
				throw new \LogicException( "Unexpected validation error source: $errorSource" );
		}
	}

	public function getCode(): string {
		return $this->code;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
