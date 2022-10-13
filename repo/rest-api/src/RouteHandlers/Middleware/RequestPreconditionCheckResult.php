<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;

/**
 * @license GPL-2.0-or-later
 */
class RequestPreconditionCheckResult {

	private ?LatestItemRevisionMetadataResult $revisionMetadata;
	private ?int $statusCode;

	private function __construct( ?LatestItemRevisionMetadataResult $revisionMetadata, ?int $statusCode ) {
		$this->revisionMetadata = $revisionMetadata;
		$this->statusCode = $statusCode;
	}

	public static function newConditionMetResult( LatestItemRevisionMetadataResult $revisionMetadata, int $statusCode ): self {
		return new self( $revisionMetadata, $statusCode );
	}

	public static function newConditionUnmetResult(): self {
		return new self( null, null );
	}

	/**
	 * Guaranteed to return a *concrete* revision result  if the request headers match the latest revision, e.g. when sending
	 * an If-None-Match header containing the latest revision ID. Returns null if there was no match.
	 */
	public function getRevisionMetadata(): ?LatestItemRevisionMetadataResult {
		return $this->revisionMetadata;
	}

	/**
	 * Returns the status code if the request headers match the latest revision, e.g. 304 when sending
	 * an If-None-Match header containing the latest revision ID. Returns null if there was no match.
	 */
	public function getStatusCode(): ?int {
		return $this->statusCode;
	}

}
