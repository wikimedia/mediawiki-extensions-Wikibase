<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescription {

	private SetItemDescriptionValidator $validator;
	private ItemRevisionMetadataRetriever $metadataRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PermissionChecker $permissionChecker;

	public function __construct(
		SetItemDescriptionValidator $validator,
		ItemRevisionMetadataRetriever $metadataRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		PermissionChecker $permissionChecker
	) {
		$this->validator = $validator;
		$this->metadataRetriever = $metadataRetriever;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->permissionChecker = $permissionChecker;
	}

	public function execute( SetItemDescriptionRequest $request ): SetItemDescriptionResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );
		$description = new Term( $request->getLanguageCode(), $request->getDescription() );

		$latestRevision = $this->metadataRetriever->getLatestRevisionMetadata( $itemId );
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

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$user = $request->getUsername() !== null ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			throw new UseCaseError(
				UseCaseError::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$descriptionExists = $item->getDescriptions()->hasTermForLanguage( $request->getLanguageCode() );
		$item->getDescriptions()->setTerm( $description );

		$editSummary = $descriptionExists
			? DescriptionEditSummary::newReplaceSummary( $request->getComment(), $description )
			: DescriptionEditSummary::newAddSummary( $request->getComment(), $description );

		$revision = $this->itemUpdater->update(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$item,
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				$editSummary
			)
		);

		return new SetItemDescriptionResponse(
			$revision->getItem()->getDescriptions()[$request->getLanguageCode()],
			$revision->getLastModified(),
			$revision->getRevisionId(),
			$descriptionExists
		);
	}
}
