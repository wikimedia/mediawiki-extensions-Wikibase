<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

/**
 * @license GPL-2.0-or-later
 */
class RequestPreconditionCheckResult {

	private ?int $revisionId;
	private ?int $statusCode;

	private function __construct( ?int $revisionId, ?int $statusCode ) {
		$this->revisionId = $revisionId;
		$this->statusCode = $statusCode;
	}

	public static function newConditionMetResult( int $revisionId, int $statusCode ): self {
		return new self( $revisionId, $statusCode );
	}

	public static function newConditionUnmetResult(): self {
		return new self( null, null );
	}

	/**
	 * Guaranteed to return a revision ID if the request headers match the latest revision, e.g. when sending
	 * an If-None-Match header containing the latest revision ID. Returns null if there was no match.
	 */
	public function getRevisionId(): ?int {
		return $this->revisionId;
	}

	/**
	 * Returns the status code if the request headers match the latest revision, e.g. 304 when sending
	 * an If-None-Match header containing the latest revision ID. Returns null if there was no match.
	 */
	public function getStatusCode(): ?int {
		return $this->statusCode;
	}

}
