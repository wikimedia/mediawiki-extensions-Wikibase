<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementGuidChangedException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Exceptions\InapplicablePatchException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedStatementException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatement {

	private $validator;
	private $statementIdParser;
	private $itemRetriever;
	private $statementPatcher;
	private $itemUpdater;
	private $revisionMetadataRetriever;
	private $permissionChecker;

	public function __construct(
		PatchItemStatementValidator $validator,
		StatementGuidParser $statementIdParser,
		ItemRetriever $itemRetriever,
		StatementPatcher $statementPatcher,
		ItemUpdater $itemUpdater,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		PermissionChecker $permissionChecker
	) {
		$this->validator = $validator;
		$this->statementIdParser = $statementIdParser;
		$this->itemRetriever = $itemRetriever;
		$this->statementPatcher = $statementPatcher;
		$this->itemUpdater = $itemUpdater;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @return PatchItemStatementSuccessResponse|PatchItemStatementErrorResponse
	 */
	public function execute( PatchItemStatementRequest $request ) {
		$validationError = $this->validator->validate( $request );
		if ( $validationError ) {
			return PatchItemStatementErrorResponse::newFromValidationError( $validationError );
		}

		$requestedItemId = $request->getItemId();
		$statementId = $this->statementIdParser->parse( $request->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $requestedItemId && !$latestRevision->itemExists() ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemId}"
			);
		} elseif ( !$latestRevision->itemExists()
			|| $latestRevision->isRedirect()
			|| !$itemId->equals( $statementId->getEntityId() ) ) {
			return $this->newStatementNotFoundErrorResponse( $statementId );
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$statementToPatch = $item->getStatements()->getFirstStatementWithGuid( (string)$statementId );

		if ( !$statementToPatch ) {
			return $this->newStatementNotFoundErrorResponse( $statementId );
		}

		$user = $request->getUsername() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::PERMISSION_DENIED,
				"You have no permission to edit this item."
			);
		}

		try {
			$patchedStatement = $this->statementPatcher->patch( $statementToPatch, $request->getPatch() );
		} catch ( InvalidPatchedSerializationException | InvalidPatchedStatementException $e ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::PATCHED_STATEMENT_INVALID,
				'The patch results in an invalid statement which cannot be stored'
			);
		} catch ( InapplicablePatchException $e ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::CANNOT_APPLY_PATCH,
				"The provided patch cannot be applied to the statement $statementId"
			);
		} catch ( PatchTestConditionFailedException $e ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::PATCH_TEST_FAILED,
				'Test operation in the patch provided failed'
			);
		}

		try {
			$item->getStatements()->replaceStatement( $statementId, $patchedStatement );
		} catch ( PropertyChangedException $e ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		} catch ( StatementGuidChangedException $e ) {
			return new PatchItemStatementErrorResponse(
				ErrorResponse::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				'Cannot change the ID of the existing statement'
			);
		}

		$newRevision = $this->itemUpdater->update(
			$item,
			new EditMetadata( $request->getEditTags(), $request->isBot(), $request->getComment() )
		);

		return new PatchItemStatementSuccessResponse(
			$newRevision->getItem()->getStatements()->getFirstStatementWithGuid( (string)$statementId ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

	private function newStatementNotFoundErrorResponse( StatementGuid $statementId ): PatchItemStatementErrorResponse {
		return new PatchItemStatementErrorResponse(
			ErrorResponse::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}

}
