<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem;

use Wikibase\Repo\Domains\Crud\Application\Serialization\ItemSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ConvertArrayObjectsToArray;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchItemEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchItem {

	use UpdateExceptionHandler;

	private PatchItemValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemRetriever $itemRetriever;
	private ItemSerializer $itemSerializer;
	private PatchJson $patchJson;
	private PatchedItemValidator $patchedItemValidator;
	private ItemWriteModelRetriever $itemWriteModelRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchItemValidator $validator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemRetriever $itemRetriever,
		ItemSerializer $itemSerializer,
		PatchJson $patchJson,
		PatchedItemValidator $patchedItemValidator,
		ItemWriteModelRetriever $itemWriteModelRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->itemRetriever = $itemRetriever;
		$this->itemSerializer = $itemSerializer;
		$this->patchJson = $patchJson;
		$this->patchedItemValidator = $patchedItemValidator;
		$this->itemWriteModelRetriever = $itemWriteModelRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchItemRequest $request ): PatchItemResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$providedMetadata = $deserializedRequest->getEditMetadata();
		$itemId = $deserializedRequest->getItemId();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $providedMetadata->getUser() );

		$item = $this->itemRetriever->getItem( $itemId );

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$originalSerialization = ConvertArrayObjectsToArray::execute( $this->itemSerializer->serialize( $item ) );
		$patchedItemSerialization = $this->patchJson->execute(
			$originalSerialization,
			$deserializedRequest->getPatch()
		);

		$originalItem = $this->itemWriteModelRetriever->getItemWriteModel( $itemId );
		$patchedItem = $this->patchedItemValidator->validateAndDeserialize(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			$patchedItemSerialization,
			$originalItem, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			$originalSerialization
		);

		$itemRevision = $this->executeWithExceptionHandling( fn() => $this->itemUpdater->update(
			$patchedItem,
			new EditMetadata(
				$providedMetadata->getTags(),
				$providedMetadata->isBot(),
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
					PatchItemEditSummary::newSummary( $providedMetadata->getComment(), $originalItem, $patchedItem )
			)
		) );

		return new PatchItemResponse(
			$itemRevision->getItem(),
			$itemRevision->getLastModified(),
			$itemRevision->getRevisionId()
		);
	}

}
