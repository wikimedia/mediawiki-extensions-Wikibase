<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services\Exceptions;

use Exception;
use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class EditPrevented extends Exception {

	private string $reason;
	private array $context;

	public function __construct(
		string $reason,
		array $context,
		string $message = '',
		int $code = 0,
		?Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->reason = $reason;
		$this->context = $context;
	}

	public function getReason(): string {
		return $this->reason;
	}

	public function getContext(): array {
		return $this->context;
	}

}
