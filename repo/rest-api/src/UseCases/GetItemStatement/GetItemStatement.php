<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatement {

	private $statementRetriever;
	private $statementSerializer;
	private $revisionMetadataRetriever;
	private $validator;

	public function __construct(
		GetItemStatementValidator $validator,
		ItemStatementRetriever $statementRetriever,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		StatementSerializer $statementSerializer
	) {
		$this->statementRetriever = $statementRetriever;
		$this->statementSerializer = $statementSerializer;
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

		$statement = $this->statementRetriever->getStatement( $statementId );
		$latestRevisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata(
			$itemId
		);

		return new GetItemStatementSuccessResponse(
			$this->statementSerializer->serialize( $statement ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
