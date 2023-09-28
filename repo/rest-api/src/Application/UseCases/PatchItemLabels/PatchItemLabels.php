<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabels {

	private AssertItemExists $assertItemExists;
	private ItemLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private PatchJson $patcher;
	private PatchedLabelsValidator $patchedLabelsValidator;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PatchItemLabelsValidator $useCaseValidator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		AssertItemExists $assertItemExists,
		ItemLabelsRetriever $labelsRetriever,
		LabelsSerializer $labelsSerializer,
		PatchJson $patcher,
		PatchedLabelsValidator $patchedLabelsValidator,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		PatchItemLabelsValidator $useCaseValidator,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->assertItemExists = $assertItemExists;
		$this->labelsRetriever = $labelsRetriever;
		$this->labelsSerializer = $labelsSerializer;
		$this->patcher = $patcher;
		$this->patchedLabelsValidator = $patchedLabelsValidator;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->useCaseValidator = $useCaseValidator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( PatchItemLabelsRequest $request ): PatchItemLabelsResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();

		$this->assertItemExists->execute( $itemId );

		$this->assertUserIsAuthorized->execute( $itemId, $deserializedRequest->getEditMetadata()->getUser()->getUsername() );

		$labels = $this->labelsRetriever->getLabels( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->labelsSerializer->serialize( $labels );

		$patchedLabels = $this->patcher->execute( iterator_to_array( $serialization ), $deserializedRequest->getPatch() );

		$item = $this->itemRetriever->getItem( $itemId );

		$originalLabels = $item->getLabels();

		$modifiedLabels = $this->patchedLabelsValidator->validateAndDeserialize( $itemId, $originalLabels, $patchedLabels );

		$item->getFingerprint()->setLabels( $modifiedLabels );

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			LabelsEditSummary::newPatchSummary( $deserializedRequest->getEditMetadata()->getComment(), $originalLabels, $modifiedLabels )
		);

		$revision = $this->itemUpdater->update(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$item,
			$editMetadata
		);

		return new PatchItemLabelsResponse( $revision->getItem()->getLabels(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
