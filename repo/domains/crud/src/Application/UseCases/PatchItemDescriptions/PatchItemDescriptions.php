<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions;

use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptions {

	use UpdateExceptionHandler;

	private PatchItemDescriptionsValidator $requestValidator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemDescriptionsRetriever $descriptionsRetriever;
	private DescriptionsSerializer $descriptionsSerializer;
	private PatchJson $patcher;
	private ItemWriteModelRetriever $itemRetriever;
	private PatchedItemDescriptionsValidator $patchedDescriptionsValidator;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchItemDescriptionsValidator $requestValidator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemDescriptionsRetriever $descriptionsRetriever,
		DescriptionsSerializer $descriptionsSerializer,
		PatchJson $patcher,
		ItemWriteModelRetriever $itemRetriever,
		PatchedItemDescriptionsValidator $patchedDescriptionsValidator,
		ItemUpdater $itemUpdater
	) {
		$this->requestValidator = $requestValidator;
		$this->assertItemExists = $assertItemExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->descriptionsRetriever = $descriptionsRetriever;
		$this->descriptionsSerializer = $descriptionsSerializer;
		$this->patcher = $patcher;
		$this->itemRetriever = $itemRetriever;
		$this->patchedDescriptionsValidator = $patchedDescriptionsValidator;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( PatchItemDescriptionsRequest $request ): PatchItemDescriptionsResponse {
		$deserializedRequest = $this->requestValidator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $deserializedRequest->getEditMetadata()->getUser() );

		$descriptions = $this->descriptionsRetriever->getDescriptions( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->descriptionsSerializer->serialize( $descriptions );

		$patchedDescriptions = $this->patcher->execute( iterator_to_array( $serialization ), $deserializedRequest->getPatch() );

		$item = $this->itemRetriever->getItemWriteModel( $itemId );
		$originalDescriptions = $item->getDescriptions();
		$modifiedDescriptions = $this->patchedDescriptionsValidator->validateAndDeserialize(
			$originalDescriptions,
			$item->getLabels(),
			$patchedDescriptions
		);

		$item->getFingerprint()->setDescriptions( $modifiedDescriptions );

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			DescriptionsEditSummary::newPatchSummary(
				$deserializedRequest->getEditMetadata()->getComment(),
				$originalDescriptions,
				$modifiedDescriptions
			)
		);

		$revision = $this->executeWithExceptionHandling( fn() => $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			$editMetadata
		) );

		return new PatchItemDescriptionsResponse(
			$revision->getItem()->getDescriptions(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
