<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\RemoveItemStatement;

use Exception;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatement {

	private RemoveItemStatementValidator $validator;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private StatementGuidParser $statementIdParser;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PermissionChecker $permissionChecker;

	public function __construct(
		RemoveItemStatementValidator $validator,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		StatementGuidParser $statementGuidParser,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		PermissionChecker $permissionChecker
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->statementIdParser = $statementGuidParser;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->permissionChecker = $permissionChecker;
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

		$revisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $requestedItemId && !$revisionMetadata->itemExists() ) {
			return new RemoveItemStatementErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemId}"
			);
		} elseif ( !$revisionMetadata->itemExists()
				   || $revisionMetadata->isRedirect()
				   || !$itemId->equals( $statementId->getEntityId() )
		) {
			return $this->newStatementNotFoundResponse( $request->getStatementId() );
		}

		$user = $request->hasUser() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			return new RemoveItemStatementErrorResponse(
				ErrorResponse::PERMISSION_DENIED,
				"You have no permission to edit this item."
			);
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$statement = $item->getStatements()->getFirstStatementWithGuid( $request->getStatementId() );
		if ( !$statement ) {
			return $this->newStatementNotFoundResponse( $request->getStatementId() );
		}

		$item->getStatements()->removeStatementsWithGuid( (string)$statementId );

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newRemoveSummary( $request->getComment(), $statement )
		);
		$this->itemUpdater->update( $item, $editMetadata );

		return new RemoveItemStatementSuccessResponse();
	}

	private function newStatementNotFoundResponse( string $statementId ): RemoveItemStatementErrorResponse {
		return new RemoveItemStatementErrorResponse(
			ErrorResponse::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}
}
