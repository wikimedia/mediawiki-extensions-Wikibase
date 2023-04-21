<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementGuidChangedException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatement {

	private PatchItemStatementValidator $useCaseValidator;
	private PatchedStatementValidator $patchedStatementValidator;
	private JsonPatcher $jsonPatcher;
	private StatementSerializer $statementSerializer;
	private StatementGuidParser $statementIdParser;
	private ItemStatementRetriever $statementRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private PermissionChecker $permissionChecker;

	public function __construct(
		PatchItemStatementValidator $useCaseValidator,
		PatchedStatementValidator $patchedStatementValidator,
		JsonPatcher $jsonPatcher,
		StatementSerializer $statementSerializer,
		StatementGuidParser $statementIdParser,
		ItemStatementRetriever $statementRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		PermissionChecker $permissionChecker
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->patchedStatementValidator = $patchedStatementValidator;
		$this->statementSerializer = $statementSerializer;
		$this->statementRetriever = $statementRetriever;
		$this->jsonPatcher = $jsonPatcher;
		$this->statementIdParser = $statementIdParser;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchItemStatementRequest $request ): PatchItemStatementResponse {
		$this->useCaseValidator->assertValidRequest( $request );

		$requestedItemId = $request->getItemId();
		$statementId = $this->statementIdParser->parse( $request->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $requestedItemId && !$latestRevision->itemExists() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemId}"
			);
		} elseif ( !$latestRevision->itemExists()
				   || $latestRevision->isRedirect()
				   || !$itemId->equals( $statementId->getEntityId() ) ) {
			$this->throwStatementNotFoundException( $statementId );
		}

		$statementToPatch = $this->statementRetriever->getStatement( $statementId );

		if ( !$statementToPatch ) {
			$this->throwStatementNotFoundException( $statementId );
		}

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$user = $request->getUsername() !== null ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			throw new UseCaseError(
				UseCaseError::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$serialization = $this->statementSerializer->serialize( $statementToPatch );

		try {
			$patchedSerialization = $this->jsonPatcher->patch( $serialization, $request->getPatch() );
		} catch ( PatchPathException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCH_TARGET_NOT_FOUND,
				"Target '{$e->getOperation()[$e->getField()]}' not found on the resource",
				[
					'operation' => $e->getOperation(),
					'field' => $e->getField(),
				]
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

		$patchedStatement = $this->patchedStatementValidator->validateAndDeserializeStatement( $patchedSerialization );

		$item = $this->itemRetriever->getItem( $itemId );

		try {
			$item->getStatements()->replaceStatement( $statementId, $patchedStatement );
		} catch ( PropertyChangedException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		} catch ( StatementGuidChangedException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				'Cannot change the ID of the existing statement'
			);
		}

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newPatchSummary( $request->getComment(), $patchedStatement )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new PatchItemStatementResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Statement validated and exists
			$newRevision->getItem()->getStatements()->getStatementById( $statementId ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

	/**
	 * @return never
	 * @throws UseCaseError
	 */
	private function throwStatementNotFoundException( StatementGuid $statementId ): void {
		throw new UseCaseError(
			UseCaseError::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}

}
