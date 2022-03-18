<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\UseCases\ErrorResult;
use Wikibase\Repo\RestApi\UseCases\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemErrorResult implements ErrorResult {
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
					ErrorResult::INVALID_ITEM_ID,
					"Not a valid item ID: " . $validationError->getValue()
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
