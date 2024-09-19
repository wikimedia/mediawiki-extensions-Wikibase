<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services\Exceptions;

use Exception;
use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class SpamBlacklistException extends Exception {

	private string $blockedText;

	public function __construct( string $blockedText, string $message = '', int $code = 0, ?Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->blockedText = $blockedText;
	}

	public function getBlockedText(): string {
		return $this->blockedText;
	}

}
