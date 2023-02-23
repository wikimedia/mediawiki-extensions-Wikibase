<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatement {

	private ItemStatementRetriever $statementRetriever;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private GetItemStatementValidator $validator;

	public function __construct(
		GetItemStatementValidator $validator,
		ItemStatementRetriever $statementRetriever,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever
	) {
		$this->statementRetriever = $statementRetriever;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseException
	 */
	public function execute( GetItemStatementRequest $statementRequest ): GetItemStatementResponse {
		$this->validator->assertValidRequest( $statementRequest );

		$statementIdParser = new StatementGuidParser( new ItemIdParser() );
		$statementId = $statementIdParser->parse( $statementRequest->getStatementId() );
		$requestedItemId = $statementRequest->getItemId();
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$latestRevisionMetadata = $this->revisionMetadataRetriever
			->getLatestRevisionMetadata( $itemId );

		if ( !$latestRevisionMetadata->itemExists() ) {
			if ( $requestedItemId ) {
				throw new UseCaseException(
					ErrorResponse::ITEM_NOT_FOUND,
					"Could not find an item with the ID: {$itemId}"
				);
			}
			$this->throwStatementNotFoundException( $statementRequest->getStatementId() );
		}

		if ( !$itemId->equals( $statementId->getEntityId() ) ) {
			$this->throwStatementNotFoundException( $statementRequest->getStatementId() );
		}

		$statement = $this->statementRetriever->getStatement( $statementId );
		if ( !$statement ) {
			$this->throwStatementNotFoundException( $statementRequest->getStatementId() );
		}

		return new GetItemStatementResponse(
			$statement,
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

	/**
	 * @throws UseCaseException
	 */
	private function throwStatementNotFoundException( string $statementId ): void {
		throw new UseCaseException(
			ErrorResponse::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}
}
