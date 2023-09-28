<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions;

use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptions {

	private PatchItemDescriptionsValidator $requestValidator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemDescriptionsRetriever $descriptionsRetriever;
	private DescriptionsSerializer $descriptionsSerializer;
	private JsonPatcher $patcher;
	private ItemRetriever $itemRetriever;
	private PatchedDescriptionsValidator $patchedDescriptionsValidator;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchItemDescriptionsValidator $requestValidator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemDescriptionsRetriever $descriptionsRetriever,
		DescriptionsSerializer $descriptionsSerializer,
		JsonPatcher $patcher,
		ItemRetriever $itemRetriever,
		PatchedDescriptionsValidator $patchedDescriptionsValidator,
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
		$this->assertUserIsAuthorized->execute( $itemId, $deserializedRequest->getEditMetadata()->getUser()->getUsername() );

		$descriptions = $this->descriptionsRetriever->getDescriptions( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->descriptionsSerializer->serialize( $descriptions );

		try {
			$patchedDescriptions = $this->patcher->patch(
				iterator_to_array( $serialization ),
				$deserializedRequest->getPatch()
			);
		} catch ( PatchPathException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCH_TARGET_NOT_FOUND,
				"Target '{$e->getOperation()[$e->getField()]}' not found on the resource",
				[ UseCaseError::CONTEXT_OPERATION => $e->getOperation(), UseCaseError::CONTEXT_FIELD => $e->getField() ]
			);
		} catch ( PatchTestConditionFailedException $e ) {
			$operation = $e->getOperation();
			throw new UseCaseError(
				UseCaseError::PATCH_TEST_FAILED,
				"Test operation in the provided patch failed. At path '${operation[ 'path' ]}'" .
				" expected '" . json_encode( $operation[ 'value' ] ) .
				"', actual: '" . json_encode( $e->getActualValue() ) . "'",
				[
					UseCaseError::CONTEXT_OPERATION => $operation,
					UseCaseError::CONTEXT_ACTUAL_VALUE => $e->getActualValue(),
				]
			);
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$originalDescriptions = $item->getDescriptions();
		$modifiedDescriptions = $this->patchedDescriptionsValidator->validateAndDeserialize(
			$itemId,
			$originalDescriptions,
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

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$revision = $this->itemUpdater->update( $item, $editMetadata );

		return new PatchItemDescriptionsResponse( $revision->getItem()->getDescriptions() );
	}

}
