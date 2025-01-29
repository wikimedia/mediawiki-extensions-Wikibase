<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use Exception;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\RequestInterface;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * Wrapper around an ItemRevisionMetadataRetriever and ConditionalHeaderUtil to check whether
 * a request meets the preconditions for a certain response code.
 *
 * @license GPL-2.0-or-later
 */
class RequestPreconditionCheck {

	private EntityRevisionLookup $revisionLookup;
	private EntityIdParser $entityIdParser;
	/** @var callable */
	private $getEntityIdFromRequest;
	private ConditionalHeaderUtil $conditionalHeaderUtil;

	public function __construct(
		EntityRevisionLookup $revisionLookup,
		EntityIdParser $entityIdParser,
		callable $getEntityIdFromRequest,
		ConditionalHeaderUtil $conditionalHeaderUtil
	) {
		$this->revisionLookup = $revisionLookup;
		$this->entityIdParser = $entityIdParser;
		$this->getEntityIdFromRequest = $getEntityIdFromRequest;
		$this->conditionalHeaderUtil = $conditionalHeaderUtil;
	}

	/**
	 * Convenience function to use with the $getEntityIdFromRequest callable and dealing with statement IDs.
	 */
	public static function getSubjectIdPrefixFromStatementId( string $statementId ): string {
		return substr( $statementId, 0, strpos( $statementId, '$' ) ?: 0 );
	}

	public function checkPreconditions( RequestInterface $request ): RequestPreconditionCheckResult {
		try {
			$entityId = $this->entityIdParser->parse(
				( $this->getEntityIdFromRequest )( $request )
			);
		} catch ( Exception $e ) {
			// Malformed IDs will be caught by validation later.
			return RequestPreconditionCheckResult::newConditionUnmetResult();
		}

		/**
		 * Calling an EntityRevisionLookup directly from the middleware violates the "flow of control"
		 * rule as it bypasses the input port and domain layer (see the 4th bullet point in ADR 1).
		 * We have made the conscious decision to allow an exception for the following reasons:
		 *  - The middleware is quite isolated and can easily be reimplement
		 *  - Doing this "properly" would result in more complex code and tests without much added
		 *    value except for "following the rules"
		 */
		return $this->revisionLookup->getLatestRevisionId( $entityId )
			->onConcreteRevision(
				fn( $revisionId, $timestamp ) => $this->getCheckResultFromRequestAndMetadata( $request, $revisionId, $timestamp )
			)
			->onRedirect( fn() => RequestPreconditionCheckResult::newConditionUnmetResult() )
			->onNonexistentEntity( fn() => RequestPreconditionCheckResult::newConditionUnmetResult() )
			->map();
	}

	private function getCheckResultFromRequestAndMetadata(
		RequestInterface $request,
		int $revisionId,
		string $revisionTimestamp
	): RequestPreconditionCheckResult {
		$this->conditionalHeaderUtil->setValidators(
			"\"{$revisionId}\"",
			$revisionTimestamp,
			true
		);

		$preconditionStatusCode = $this->conditionalHeaderUtil->checkPreconditions( $request );

		return $preconditionStatusCode ?
			RequestPreconditionCheckResult::newConditionMetResult( $revisionId, $preconditionStatusCode ) :
			RequestPreconditionCheckResult::newConditionUnmetResult();
	}

}
