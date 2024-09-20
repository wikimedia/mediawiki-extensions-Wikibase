<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services\Exceptions;

use Exception;
use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class AbuseFilterException extends Exception {

	private int $filterId;
	private string $filterDescription;

	public function __construct(
		int $filterId,
		string $filterDescription,
		string $message = '',
		int $code = 0,
		?Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->filterId = $filterId;
		$this->filterDescription = $filterDescription;
	}

	public function getFilterId(): int {
		return $this->filterId;
	}

	public function getFilterDescription(): string {
		return $this->filterDescription;
	}

}
