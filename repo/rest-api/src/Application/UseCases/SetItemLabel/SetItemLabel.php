<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabel {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PermissionChecker $permissionChecker;
	private SetItemLabelValidator $validator;

	public function __construct(
		SetItemLabelValidator $validator,
		GetLatestItemRevisionMetadata $getRevisionMetadata,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		PermissionChecker $permissionChecker
	) {
		$this->validator = $validator;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->permissionChecker = $permissionChecker;
	}

	public function execute( SetItemLabelRequest $request ): SetItemLabelResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );
		$term = new Term( $request->getLanguageCode(), $request->getLabel() );

		$this->getRevisionMetadata->execute( $itemId ); // checks redirect and item existence
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$user = $request->getUsername() !== null ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			throw new UseCaseError(
				UseCaseError::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$labelExists = $item->getLabels()->hasTermForLanguage( $request->getLanguageCode() );
		$item->getLabels()->setTerm( $term );

		$editSummary = $labelExists
			? LabelEditSummary::newReplaceSummary( $request->getComment(), $term )
			: LabelEditSummary::newAddSummary( $request->getComment(), $term );

		$editMetadata = new EditMetadata( $request->getEditTags(), $request->isBot(), $editSummary );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new SetItemLabelResponse(
			$newRevision->getItem()->getLabels()[$request->getLanguageCode()],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$labelExists
		);
	}

}
