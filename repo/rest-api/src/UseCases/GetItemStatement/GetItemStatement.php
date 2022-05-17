<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatement {

	private $statementRetriever;
	private $revisionMetadataRetriever;
	private $validator;

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
	 * @return GetItemStatementSuccessResponse | GetItemStatementErrorResponse
	 */
	public function execute( GetItemStatementRequest $statementRequest ) {
		$validationError = $this->validator->validate( $statementRequest );
		if ( $validationError ) {
			return GetItemStatementErrorResponse::newFromValidationError( $validationError );
		}

		$statementIdParser = new StatementGuidParser( new ItemIdParser() );
		$statementId = $statementIdParser->parse( $statementRequest->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$latestRevisionMetadata = $this->revisionMetadataRetriever
			->getLatestRevisionMetadata( $itemId );
		$statement = $latestRevisionMetadata->itemExists() ?
			$this->statementRetriever->getStatement( $statementId ) : null;

		if ( !$statement ) {
			return new GetItemStatementErrorResponse(
				ErrorResponse::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$statementRequest->getStatementId()}"
			);
		}

		return new GetItemStatementSuccessResponse(
			$statement,
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
