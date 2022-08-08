<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatement {

	private $validator;
	private $revisionMetadataRetriever;
	private $itemRetriever;
	private $itemUpdater;
	private $permissionChecker;

	public function __construct(
		ReplaceItemStatementValidator $validator,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		PermissionChecker $permissionChecker
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @return ReplaceItemStatementSuccessResponse|ReplaceItemStatementErrorResponse
	 */
	public function execute( ReplaceItemStatementRequest $request ) {
		$validationError = $this->validator->validate( $request );
		if ( $validationError ) {
			return ReplaceItemStatementErrorResponse::newFromValidationError( $validationError );
		}

		$requestedItemId = $request->getItemId();
		$statementIdParser = new StatementGuidParser( new ItemIdParser() );
		$statementId = $statementIdParser->parse( $request->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $requestedItemId && !$latestRevision->itemExists() ) {
			return new ReplaceItemStatementErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemId}"
			);
		} elseif ( !$latestRevision->itemExists() ||
			$latestRevision->isRedirect() ||
			!$itemId->equals( $statementId->getEntityId() ) ) {
			return $this->newStatementNotFoundErrorResponse( $statementId );
		}

		$user = $request->hasUser() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			return new ReplaceItemStatementErrorResponse(
				ErrorResponse::PERMISSION_DENIED,
				"You have no permission to edit this item."
			);
		}

		$newStatement = $this->validator->getValidatedStatement();

		$item = $this->itemRetriever->getItem( $itemId );
		if ( !$item->getStatements()->getFirstStatementWithGuid( (string)$statementId ) ) {
			return $this->newStatementNotFoundErrorResponse( $statementId );
		}

		try {
			$item->getStatements()->replaceStatement( $statementId, $newStatement );
		} catch ( InvalidArgumentException $exception ) {
			return new ReplaceItemStatementErrorResponse(
				ErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		}

		$newRevision = $this->itemUpdater->update(
			$item,
			new EditMetadata( $request->getEditTags(), $request->isBot(), $request->getComment() )
		);

		return new ReplaceItemStatementSuccessResponse(
			$newRevision->getItem()->getStatements()->getFirstStatementWithGuid( (string)$statementId ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

	private function newStatementNotFoundErrorResponse( StatementGuid $statementId ): ReplaceItemStatementErrorResponse {
		return new ReplaceItemStatementErrorResponse(
			ErrorResponse::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}

}
