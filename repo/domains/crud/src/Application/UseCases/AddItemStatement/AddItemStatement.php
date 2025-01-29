<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemStatement;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\StatementEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatement {

	use UpdateExceptionHandler;

	private AddItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private GuidGenerator $guidGenerator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		AddItemStatementValidator $validator,
		AssertItemExists $assertItemExists,
		ItemWriteModelRetriever $itemRetriever,
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
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$statement = $deserializedRequest->getStatement();

		$this->assertItemExists->execute( $itemId );

		$editMetadata = $deserializedRequest->getEditMetadata();
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $editMetadata->getUser() );

		$newStatementGuid = $this->guidGenerator->newStatementId( $itemId );
		$statement->setGuid( (string)$newStatementGuid );
		$item = $this->itemRetriever->getItemWriteModel( $itemId );
		$item->getStatements()->addStatement( $statement );

		$newRevision = $this->executeWithExceptionHandling( fn() => $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable Item validated and exists
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				StatementEditSummary::newAddSummary( $editMetadata->getComment(), $statement )
			)
		) );

		return new AddItemStatementResponse(
			$newRevision->getItem()->getStatements()->getStatementById( $newStatementGuid ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
