<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use Wikibase\Repo\RestApi\Application\Serialization\ItemSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ConvertArrayObjectsToArray;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchItem {

	private PatchItemValidator $validator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemRetriever $itemRetriever;
	private ItemSerializer $itemSerializer;
	private PatchJson $patchJson;
	private ItemUpdater $itemUpdater;
	private PatchedItemValidator $patchedItemValidator;
	private ItemWriteModelRetriever $itemWriteModelRetriever;

	public function __construct(
		PatchItemValidator $validator,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemRetriever $itemRetriever,
		ItemWriteModelRetriever $itemWriteModelRetriever,
		ItemSerializer $itemSerializer,
		PatchJson $patchJson,
		ItemUpdater $itemUpdater,
		PatchedItemValidator $patchedItemValidator
	) {
		$this->validator = $validator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->itemRetriever = $itemRetriever;
		$this->itemWriteModelRetriever = $itemWriteModelRetriever;
		$this->itemSerializer = $itemSerializer;
		$this->patchJson = $patchJson;
		$this->itemUpdater = $itemUpdater;
		$this->patchedItemValidator = $patchedItemValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchItemRequest $request ): PatchItemResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$providedMetadata = $deserializedRequest->getEditMetadata();
		$itemId = $deserializedRequest->getItemId();
		$originalItem = $this->itemWriteModelRetriever->getItemWriteModel( $itemId );

		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $providedMetadata->getUser() );

		$patchedItemSerialization = $this->patchJson->execute(
			ConvertArrayObjectsToArray::execute(
				$this->itemSerializer->serialize(
					// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
					$this->itemRetriever->getItem( $itemId )
				)
			),
			$deserializedRequest->getPatch()
		);

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$patchedItem = $this->patchedItemValidator->validateAndDeserialize( $patchedItemSerialization, $originalItem );

		$itemRevision = $this->itemUpdater->update(
			$patchedItem,
			new EditMetadata(
				$providedMetadata->getTags(),
				$providedMetadata->isBot(),
				ItemEditSummary::newPatchSummary( $providedMetadata->getComment() )
			)
		);

		return new PatchItemResponse(
			$itemRevision->getItem(),
			$itemRevision->getLastModified(),
			$itemRevision->getRevisionId()
		);
	}

}
