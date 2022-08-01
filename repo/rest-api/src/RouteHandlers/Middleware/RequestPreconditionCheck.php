<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use Exception;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\RequestInterface;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * Wrapper around an ItemRevisionMetadataRetriever and ConditionalHeaderUtil to check whether
 * a request meets the preconditions for a certain response code.
 *
 * @license GPL-2.0-or-later
 */
class RequestPreconditionCheck {
	private $metadataRetriever;
	private $getItemIdFromRequest;
	private $conditionalHeaderUtil;

	public function __construct(
		ItemRevisionMetadataRetriever $metadataRetriever,
		callable $getItemIdFromRequest,
		ConditionalHeaderUtil $conditionalHeaderUtil
	) {
		$this->metadataRetriever = $metadataRetriever;
		$this->getItemIdFromRequest = $getItemIdFromRequest;
		$this->conditionalHeaderUtil = $conditionalHeaderUtil;
	}

	/**
	 * Convenience function to use with the $getItemIdFromRequest callable and dealing with statement IDs.
	 */
	public static function getItemIdPrefixFromStatementId( string $statementId ): string {
		return substr( $statementId, 0, strpos( $statementId, '$' ) ?: 0 );
	}

	public function checkPreconditions( RequestInterface $request ): RequestPreconditionCheckResult {
		try {
			$itemId = new ItemId(
				( $this->getItemIdFromRequest )( $request )
			);
		} catch ( Exception $e ) {
			// Malformed IDs will be caught by validation later.
			return RequestPreconditionCheckResult::newConditionUnmetResult();
		}

		$itemMetadata = $this->metadataRetriever->getLatestRevisionMetadata( $itemId );
		$preconditionStatusCode = $this->getStatusCodeFromRequestAndMetadata( $request, $itemMetadata );

		return $preconditionStatusCode ?
			RequestPreconditionCheckResult::newConditionMetResult( $itemMetadata, $preconditionStatusCode ) :
			RequestPreconditionCheckResult::newConditionUnmetResult();
	}

	private function getStatusCodeFromRequestAndMetadata(
		RequestInterface $request,
		LatestItemRevisionMetadataResult $revisionMetadata
	): ?int {
		if ( !$revisionMetadata->itemExists() || $revisionMetadata->isRedirect() ) {
			return null;
		}

		$this->conditionalHeaderUtil->setValidators(
			"\"{$revisionMetadata->getRevisionId()}\"",
			$revisionMetadata->getRevisionTimestamp(),
			true
		);

		return $this->conditionalHeaderUtil->checkPreconditions( $request );
	}

}
