<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabels {

	private AssertItemExists $assertItemExists;
	private ItemLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private JsonPatcher $patcher;
	private PatchedLabelsValidator $patchedLabelsValidator;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PatchItemLabelsValidator $useCaseValidator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		AssertItemExists $assertItemExists,
		ItemLabelsRetriever $labelsRetriever,
		LabelsSerializer $labelsSerializer,
		JsonPatcher $patcher,
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
		$this->useCaseValidator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		$this->assertItemExists->execute( $itemId );

		$this->assertUserIsAuthorized->execute( $itemId, $request->getUsername() );

		$labels = $this->labelsRetriever->getLabels( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->labelsSerializer->serialize( $labels );

		try {
			$patchedLabels = $this->patcher->patch( iterator_to_array( $serialization ), $request->getPatch() );
		} catch ( PatchPathException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCH_TARGET_NOT_FOUND,
				"Target '{$e->getOperation()[$e->getField()]}' not found on the resource",
				[ 'operation' => $e->getOperation(), 'field' => $e->getField() ]
			);
		} catch ( PatchTestConditionFailedException $e ) {
			$operation = $e->getOperation();
			throw new UseCaseError(
				UseCaseError::PATCH_TEST_FAILED,
				'Test operation in the provided patch failed. ' .
				"At path '" . $operation['path'] .
				"' expected '" . json_encode( $operation['value'] ) .
				"', actual: '" . json_encode( $e->getActualValue() ) . "'",
				[ 'operation' => $operation, 'actual-value' => $e->getActualValue() ]
			);
		}

		$item = $this->itemRetriever->getItem( $itemId );

		$originalLabels = $item->getLabels();

		$modifiedLabels = $this->patchedLabelsValidator->validateAndDeserialize( $itemId, $originalLabels, $patchedLabels );

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
