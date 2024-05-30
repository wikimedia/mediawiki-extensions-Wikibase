<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ItemSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ConvertArrayObjectsToArray;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchItem {

	private PatchItemValidator $validator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemRetriever $itemRetriever;
	private ItemSerializer $itemSerializer;
	private ItemDeserializer $itemDeserializer;
	private PatchJson $patchJson;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchItemValidator $validator,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemRetriever $itemRetriever,
		ItemSerializer $itemSerializer,
		ItemDeserializer $itemDeserializer,
		PatchJson $patchJson,
		ItemUpdater $itemUpdater
	) {
		$this->validator = $validator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->itemRetriever = $itemRetriever;
		$this->itemSerializer = $itemSerializer;
		$this->itemDeserializer = $itemDeserializer;
		$this->patchJson = $patchJson;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchItemRequest $request ): PatchItemResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$providedMetadata = $deserializedRequest->getEditMetadata();

		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $providedMetadata->getUser() );

		$patchedItem = $this->itemDeserializer->deserialize(
			$this->patchJson->execute(
				ConvertArrayObjectsToArray::execute(
					$this->itemSerializer->serialize(
						// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
						$this->itemRetriever->getItem( $itemId )
					)
				),
				$deserializedRequest->getPatch()
			)
		);

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
