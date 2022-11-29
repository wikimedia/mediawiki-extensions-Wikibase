<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\AddItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
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
class AddItemStatement {

	private AddItemStatementValidator $validator;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private GuidGenerator $guidGenerator;
	private PermissionChecker $permissionChecker;

	public function __construct(
		AddItemStatementValidator $validator,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		GuidGenerator $guidGenerator,
		PermissionChecker $permissionChecker
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->guidGenerator = $guidGenerator;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @return AddItemStatementSuccessResponse | AddItemStatementErrorResponse
	 */
	public function execute( AddItemStatementRequest $request ) {
		$validationError = $this->validator->validate( $request );

		if ( $validationError ) {
			return AddItemStatementErrorResponse::newFromValidationError( $validationError );
		}

		$statement = $this->validator->getValidatedStatement();
		$itemId = new ItemId( $request->getItemId() );

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $latestRevision->isRedirect() ) {
			return new AddItemStatementErrorResponse(
				ErrorResponse::ITEM_REDIRECTED,
				"Item {$request->getItemId()} has been merged into {$latestRevision->getRedirectTarget()}."
			);
		} elseif ( !$latestRevision->itemExists() ) {
			return new AddItemStatementErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		}
		$user = $request->hasUser() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			return new AddItemStatementErrorResponse(
				ErrorResponse::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$newStatementGuid = $this->guidGenerator->newGuid( $itemId );
		$statement->setGuid( $newStatementGuid );

		$item = $this->itemRetriever->getItem( $itemId );
		$item->getStatements()->addStatement( $statement );

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newAddSummary( $request->getComment(), $statement )
		);
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new AddItemStatementSuccessResponse(
			$newRevision->getItem()->getStatements()->getFirstStatementWithGuid( $newStatementGuid ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
