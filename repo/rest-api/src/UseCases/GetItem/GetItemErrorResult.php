<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\UseCases\ErrorResult;

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

	public function getCode(): string {
		return $this->code;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
