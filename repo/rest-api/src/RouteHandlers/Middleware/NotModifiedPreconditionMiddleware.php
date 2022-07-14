<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use Exception;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class NotModifiedPreconditionMiddleware implements Middleware {
	private $metadataRetriever;
	private $getItemIdFromRequest;

	public function __construct( ItemRevisionMetadataRetriever $metadataRetriever, callable $getItemIdFromRequest ) {
		$this->metadataRetriever = $metadataRetriever;
		$this->getItemIdFromRequest = $getItemIdFromRequest;
	}

	/**
	 * Convenience function to use with the $getItemIdFromRequest callable and dealing with statement IDs.
	 */
	public static function getItemIdPrefixFromStatementId( string $statementId ): string {
		return substr( $statementId, 0, strpos( $statementId, '$' ) ?: 0 );
	}

	public function run( Handler $handler, callable $runNext ): Response {
		try {
			$itemId = new ItemId(
				( $this->getItemIdFromRequest )( $handler->getRequest() )
			);
		} catch ( Exception $e ) {
			// Do nothing and just continue. Malformed IDs will be caught by validation later.
			return $runNext();
		}

		$itemMetadata = $this->metadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $itemMetadata->itemExists() &&
			!$itemMetadata->isRedirect() &&
			$this->isNotModified( $handler, $itemMetadata->getRevisionId(), $itemMetadata->getRevisionTimestamp() ) ) {
			return $this->newNotModifiedResponse( $handler,  $itemMetadata->getRevisionId() );
		}

		return $runNext();
	}

	private function isNotModified( Handler $handler, int $revId, string $lastModifiedDate ): bool {
		$headerUtil = new ConditionalHeaderUtil();
		$headerUtil->setValidators( "\"$revId\"", $lastModifiedDate, true );

		return $headerUtil->checkPreconditions( $handler->getRequest() ) === 304;
	}

	private function newNotModifiedResponse( Handler $handler, int $revId ): Response {
		$notModifiedResponse = $handler->getResponseFactory()->createNotModified();
		$notModifiedResponse->setHeader( 'ETag', "\"$revId\"" );

		return $notModifiedResponse;
	}

}
