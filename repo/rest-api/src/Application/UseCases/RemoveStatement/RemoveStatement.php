<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
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
class RemoveStatement {

	private RemoveStatementValidator $validator;
	private StatementGuidParser $statementIdParser;
	private AssertItemExists $assertItemExists;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		RemoveStatementValidator $validator,
		StatementGuidParser $statementGuidParser,
		AssertItemExists $assertItemExists,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->statementIdParser = $statementGuidParser;
		$this->assertItemExists = $assertItemExists;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( RemoveStatementRequest $request ): void {
		$this->validator->assertValidRequest( $request );

		$statementId = $this->statementIdParser->parse( $request->getStatementId() );
		$requestedItemId = $request->getItemId();
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		$this->assertItemExists->execute( $itemId );

		if ( !$itemId->equals( $statementId->getEntityId() ) ) {
			$this->throwStatementNotFoundException( $request->getStatementId() );
		}

		$this->assertUserIsAuthorized->execute( $itemId, $request->getUsername() );

		$item = $this->itemRetriever->getItem( $itemId );
		$statement = $item->getStatements()->getFirstStatementWithGuid( $request->getStatementId() );
		if ( !$statement ) {
			$this->throwStatementNotFoundException( $request->getStatementId() );
		}

		$item->getStatements()->removeStatementsWithGuid( (string)$statementId );

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newRemoveSummary( $request->getComment(), $statement )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
		$this->itemUpdater->update( $item, $editMetadata );
	}

	/**
	 * @return never
	 * @throws UseCaseError
	 */
	private function throwStatementNotFoundException( string $statementId ): void {
		throw new UseCaseError(
			UseCaseError::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}
}
