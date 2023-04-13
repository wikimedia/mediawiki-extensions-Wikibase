<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;

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
	 * @throws UseCaseError
	 */
	public function execute( AddItemStatementRequest $request ): AddItemStatementResponse {
		$this->validator->assertValidRequest( $request );

		$statement = $this->validator->getValidatedStatement();
		$itemId = new ItemId( $request->getItemId() );

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $latestRevision->isRedirect() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_REDIRECTED,
				"Item {$request->getItemId()} has been merged into {$latestRevision->getRedirectTarget()}."
			);
		} elseif ( !$latestRevision->itemExists() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		}
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable hasUser checks for null
		$user = $request->hasUser() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			throw new UseCaseError(
				UseCaseError::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$newStatementGuid = $this->guidGenerator->newStatementId( $itemId );
		$statement->setGuid( (string)$newStatementGuid );
		$item = $this->itemRetriever->getItem( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Statement is validated and exists
		$item->getStatements()->addStatement( $statement );

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Statement is validated and exists
			StatementEditSummary::newAddSummary( $request->getComment(), $statement )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new AddItemStatementResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$newRevision->getItem()->getStatements()->getStatementById( $newStatementGuid ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
