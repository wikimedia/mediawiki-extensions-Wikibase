<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatement {

	private AddItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private GuidGenerator $guidGenerator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		AddItemStatementValidator $validator,
		AssertItemExists $assertItemExists,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		GuidGenerator $guidGenerator,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->guidGenerator = $guidGenerator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( AddItemStatementRequest $request ): AddItemStatementResponse {
		$this->validator->assertValidRequest( $request );

		$statement = $this->validator->getValidatedStatement();
		$itemId = new ItemId( $request->getItemId() );

		$this->assertItemExists->execute( $itemId );

		$this->assertUserIsAuthorized->execute( $itemId, $request->getUsername() );

		$newStatementGuid = $this->guidGenerator->newStatementId( $itemId );
		$statement->setGuid( (string)$newStatementGuid );
		$item = $this->itemRetriever->getItem( $itemId );
		$item->getStatements()->addStatement( $statement );

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newAddSummary( $request->getComment(), $statement )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new AddItemStatementResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$newRevision->getItem()->getStatements()->getStatementById( $newStatementGuid ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
