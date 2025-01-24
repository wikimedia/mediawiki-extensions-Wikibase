<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels;

use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabels {

	use UpdateExceptionHandler;

	private AssertItemExists $assertItemExists;
	private ItemLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private PatchJson $patcher;
	private PatchedItemLabelsValidator $patchedLabelsValidator;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PatchItemLabelsValidator $useCaseValidator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		AssertItemExists $assertItemExists,
		ItemLabelsRetriever $labelsRetriever,
		LabelsSerializer $labelsSerializer,
		PatchJson $patcher,
		PatchedItemLabelsValidator $patchedLabelsValidator,
		ItemWriteModelRetriever $itemRetriever,
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

		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $deserializedRequest->getEditMetadata()->getUser() );

		$labels = $this->labelsRetriever->getLabels( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->labelsSerializer->serialize( $labels );

		$patchedLabels = $this->patcher->execute( iterator_to_array( $serialization ), $deserializedRequest->getPatch() );

		$item = $this->itemRetriever->getItemWriteModel( $itemId );
		$originalLabels = $item->getLabels();
		$modifiedLabels = $this->patchedLabelsValidator->validateAndDeserialize(
			$originalLabels,
			$item->getDescriptions(),
			$patchedLabels
		);

		$item->getFingerprint()->setLabels( $modifiedLabels );

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			LabelsEditSummary::newPatchSummary( $deserializedRequest->getEditMetadata()->getComment(), $originalLabels, $modifiedLabels )
		);

		$revision = $this->executeWithExceptionHandling( fn() => $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			$editMetadata
		) );

		return new PatchItemLabelsResponse( $revision->getItem()->getLabels(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
