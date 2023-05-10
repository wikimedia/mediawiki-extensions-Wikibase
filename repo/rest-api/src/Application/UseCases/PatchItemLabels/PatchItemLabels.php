<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabels {

	private ItemLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private JsonPatcher $patcher;
	private LabelsDeserializer $labelsDeserializer;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private PermissionChecker $permissionChecker;

	public function __construct(
		ItemLabelsRetriever $labelsRetriever,
		LabelsSerializer $labelsSerializer,
		JsonPatcher $patcher,
		LabelsDeserializer $labelsDeserializer,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		PermissionChecker $permissionChecker
	) {
		$this->labelsRetriever = $labelsRetriever;
		$this->labelsSerializer = $labelsSerializer;
		$this->patcher = $patcher;
		$this->labelsDeserializer = $labelsDeserializer;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->permissionChecker = $permissionChecker;
	}

	public function execute( PatchItemLabelsRequest $request ): PatchItemLabelsResponse {
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

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$user = $request->getUsername() !== null ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			throw new UseCaseError(
				UseCaseError::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$labels = $this->labelsRetriever->getLabels( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->labelsSerializer->serialize( $labels );
		$patchResult = $this->patcher->patch( iterator_to_array( $serialization ), $request->getPatch() );
		$modifiedLabels = $this->labelsDeserializer->deserialize( $patchResult );

		$item = $this->itemRetriever->getItem( $itemId );

		$originalLabels = $item->getLabels();

		$item->getFingerprint()->setLabels( $modifiedLabels );

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			LabelsEditSummary::newPatchSummary( $request->getComment(), $originalLabels, $modifiedLabels )
		);

		$revision = $this->itemUpdater->update(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$item,
			$editMetadata
		);

		return new PatchItemLabelsResponse( $revision->getItem()->getLabels(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
