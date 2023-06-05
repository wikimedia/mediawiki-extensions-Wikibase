<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementGuidChangedException;
use Wikibase\DataModel\Exception\StatementNotFoundException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatement {

	private ReplaceItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		ReplaceItemStatementValidator $validator,
		AssertItemExists $assertItemExists,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( ReplaceItemStatementRequest $request ): ReplaceItemStatementResponse {
		$this->validator->assertValidRequest( $request );

		$requestedItemId = $request->getItemId();
		$statementIdParser = new StatementGuidParser( new ItemIdParser() );
		$statementId = $statementIdParser->parse( $request->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$this->assertItemExists->execute( $itemId );

		if ( !$itemId->equals( $statementId->getEntityId() ) ) {
			$this->throwStatementNotFoundException( $statementId );
		}

		$this->assertUserIsAuthorized->execute( $itemId, $request->getUsername() );

		$item = $this->itemRetriever->getItem( $itemId );
		$newStatement = $this->validator->getValidatedStatement();

		try {
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable NewStatement is validated and exists
			$item->getStatements()->replaceStatement( $statementId, $newStatement );
		} catch ( StatementNotFoundException $e ) {
			$this->throwStatementNotFoundException( $statementId );
		} catch ( StatementGuidChangedException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				'Cannot change the ID of the existing statement'
			);
		} catch ( PropertyChangedException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		}

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable NewStatement is validated and exists
			StatementEditSummary::newReplaceSummary( $request->getComment(), $newStatement )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item is validated and exists
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new ReplaceItemStatementResponse(
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Statement is validated and exists
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
