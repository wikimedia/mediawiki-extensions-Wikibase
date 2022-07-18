<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\RemoveItemStatement;

use Exception;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatement {

	private $validator;
	private $revisionMetadataRetriever;
	private $statementIdParser;
	private $itemRetriever;
	private $itemUpdater;

	public function __construct(
		RemoveItemStatementValidator $validator,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		StatementGuidParser $statementGuidParser,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->statementIdParser = $statementGuidParser;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @return RemoveItemStatementSuccessResponse | RemoveItemStatementErrorResponse
	 * @throws Exception
	 */
	public function execute( RemoveItemStatementRequest $request ) {
		$validationError = $this->validator->validate( $request );
		if ( $validationError ) {
			return RemoveItemStatementErrorResponse::newFromValidationError( $validationError );
		}

		$statementId = $this->statementIdParser->parse( $request->getStatementId() );
		$requestedItemId = $request->getItemId();
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		// T312559: handle item not found and item redirect

		$item = $this->itemRetriever->getItem( $itemId );
		$item->getStatements()->removeStatementsWithGuid( $statementId );

		$editMetadata = new EditMetadata( $request->getEditTags(), $request->isBot(), $request->getComment() );
		$itemRevision = $this->itemUpdater->update( $item, $editMetadata );
		if ( !$itemRevision
			 || $itemRevision->getItem()->getStatements()->getFirstStatementWithGuid( $statementId ) ) {
			throw new Exception( 'Item update failed' );
		}

		return new RemoveItemStatementSuccessResponse();
	}
}
