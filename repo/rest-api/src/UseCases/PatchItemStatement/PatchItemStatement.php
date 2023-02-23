<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementGuidChangedException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatement {

	private PatchItemStatementValidator $useCaseValidator;
	private JsonPatcher $jsonPatcher;
	private StatementSerializer $statementSerializer;
	private StatementValidator $statementValidator;
	private StatementGuidParser $statementIdParser;
	private ItemStatementRetriever $statementRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private PermissionChecker $permissionChecker;

	public function __construct(
		PatchItemStatementValidator $useCaseValidator,
		JsonPatcher $jsonPatcher,
		StatementSerializer $statementSerializer,
		StatementValidator $statementValidator,
		StatementGuidParser $statementIdParser,
		ItemStatementRetriever $statementRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		PermissionChecker $permissionChecker
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->statementSerializer = $statementSerializer;
		$this->statementValidator = $statementValidator;
		$this->statementRetriever = $statementRetriever;
		$this->jsonPatcher = $jsonPatcher;
		$this->statementIdParser = $statementIdParser;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @throws UseCaseException
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
			throw new UseCaseException(
				ErrorResponse::ITEM_NOT_FOUND,
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

		$user = $request->getUsername() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			throw new UseCaseException(
				ErrorResponse::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$serialization = $this->statementSerializer->serialize( $statementToPatch );

		try {
			$patchedSerialization = $this->jsonPatcher->patch( $serialization, $request->getPatch() );
		} catch ( PatchPathException $e ) {
			throw new UseCaseException(
				ErrorResponse::PATCH_TARGET_NOT_FOUND,
				"Target '{$e->getOperation()[$e->getField()]}' not found on the resource",
				[
					'operation' => $e->getOperation(),
					'field' => $e->getField(),
				]
			);
		} catch ( PatchTestConditionFailedException $e ) {
			$operation = $e->getOperation();
			throw new UseCaseException(
				ErrorResponse::PATCH_TEST_FAILED,
				'Test operation in the provided patch failed. ' .
				"At path '" . $operation['path'] .
				"' expected '" . json_encode( $operation['value'] ) .
				"', actual: '" . json_encode( $e->getActualValue() ) . "'",
				[ 'operation' => $operation, 'actual-value' => $e->getActualValue() ]
			);
		}

		$postPatchValidationError = $this->statementValidator->validate( $patchedSerialization );
		if ( $postPatchValidationError ) {
			 PatchItemStatementValidator::throwUseCaseExceptionFromValidationError( $postPatchValidationError );
		}

		$patchedStatement = $this->statementValidator->getValidatedStatement();
		$item = $this->itemRetriever->getItem( $itemId );

		try {
			$item->getStatements()->replaceStatement( $statementId, $patchedStatement );
		} catch ( PropertyChangedException $e ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		} catch ( StatementGuidChangedException $e ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				'Cannot change the ID of the existing statement'
			);
		}

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newPatchSummary( $request->getComment(), $patchedStatement )
		);
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new PatchItemStatementResponse(
			$newRevision->getItem()->getStatements()->getStatementById( $statementId ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

	/**
	 * @throws UseCaseException
	 */
	private function throwStatementNotFoundException( StatementGuid $statementId ): void {
		throw new UseCaseException(
			ErrorResponse::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}

}
