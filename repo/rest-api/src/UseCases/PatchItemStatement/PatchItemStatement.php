<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatement {

	private $validator;
	private $statementIdParser;
	private $itemRetriever;
	private $statementPatcher;
	private $itemUpdater;
	private $revisionMetadataRetriever;

	public function __construct(
		PatchItemStatementValidator $validator,
		StatementGuidParser $statementIdParser,
		ItemRetriever $itemRetriever,
		StatementPatcher $statementPatcher,
		ItemUpdater $itemUpdater,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever
	) {
		$this->validator = $validator;
		$this->statementIdParser = $statementIdParser;
		$this->itemRetriever = $itemRetriever;
		$this->statementPatcher = $statementPatcher;
		$this->itemUpdater = $itemUpdater;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
	}

	/**
	 * @return PatchItemStatementSuccessResponse|PatchItemStatementErrorResponse
	 */
	public function execute( PatchItemStatementRequest $request ) {
		$validationError = $this->validator->validate( $request );
		if ( $validationError ) {
			return PatchItemStatementErrorResponse::newFromValidationError( $validationError );
		}

		$requestedItemId = $request->getItemId();
		$statementId = $this->statementIdParser->parse( $request->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $requestedItemId && !$latestRevision->itemExists() ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemId}"
			);
		} elseif ( !$latestRevision->itemExists()
			|| $latestRevision->isRedirect()
			|| !$itemId->equals( $statementId->getEntityId() ) ) {
			return $this->newStatementNotFoundErrorResponse( $statementId );
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$statementToPatch = $item->getStatements()->getFirstStatementWithGuid( (string)$statementId );

		if ( !$statementToPatch ) {
			return $this->newStatementNotFoundErrorResponse( $statementId );
		}

		$patchedStatement = $this->statementPatcher->patch( $statementToPatch, $request->getPatch() );

		// TODO: handle errors caused by patching (T316319)
		// TODO: validate patched statement (T316316)

		$item->getStatements()->replaceStatement( $statementId, $patchedStatement );

		$newRevision = $this->itemUpdater->update(
			$item,
			new EditMetadata( $request->getEditTags(), $request->isBot(), $request->getComment() )
		);

		return new PatchItemStatementSuccessResponse(
			$newRevision->getItem()->getStatements()->getFirstStatementWithGuid( (string)$statementId ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

	private function newStatementNotFoundErrorResponse( StatementGuid $statementId ): PatchItemStatementErrorResponse {
		return new PatchItemStatementErrorResponse(
			ErrorResponse::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}

}
