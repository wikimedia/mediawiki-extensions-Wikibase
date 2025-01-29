<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services\Exceptions;

use Exception;
use Throwable;

/**
 * @license GPL-2.0-or-later
 */
class ResourceTooLargeException extends Exception {

	private int $resourceSizeLimit;

	public function __construct( int $resourceSizeLimit, string $message = '', int $code = 0, ?Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->resourceSizeLimit = $resourceSizeLimit;
	}

	public function getResourceSizeLimit(): int {
		return $this->resourceSizeLimit;
	}

}
