<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementGuidChangedException;
use Wikibase\DataModel\Exception\StatementNotFoundException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatement {

	private ReplaceItemStatementValidator $validator;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PermissionChecker $permissionChecker;

	public function __construct(
		ReplaceItemStatementValidator $validator,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		PermissionChecker $permissionChecker
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @throws UseCaseException
	 */
	public function execute( ReplaceItemStatementRequest $request ): ReplaceItemStatementResponse {
		$this->validator->assertValidRequest( $request );

		$requestedItemId = $request->getItemId();
		$statementIdParser = new StatementGuidParser( new ItemIdParser() );
		$statementId = $statementIdParser->parse( $request->getStatementId() );
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

		$user = $request->hasUser() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $itemId ) ) {
			throw new UseCaseException(
				ErrorResponse::PERMISSION_DENIED,
				'You have no permission to edit this item.'
			);
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$newStatement = $this->validator->getValidatedStatement();

		try {
			$item->getStatements()->replaceStatement( $statementId, $newStatement );
		} catch ( StatementNotFoundException $e ) {
			$this->throwStatementNotFoundException( $statementId );
		} catch ( StatementGuidChangedException $e ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				'Cannot change the ID of the existing statement'
			);
		} catch ( PropertyChangedException $e ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		}

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newReplaceSummary( $request->getComment(), $newStatement )
		);
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new ReplaceItemStatementResponse(
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
