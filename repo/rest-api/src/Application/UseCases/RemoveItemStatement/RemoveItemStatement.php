<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatement {

	private AssertItemExists $assertItemExists;
	private RemoveStatement $removeStatement;

	public function __construct(
		AssertItemExists $assertItemExists,
		RemoveStatement $removeStatement
	) {
		$this->assertItemExists = $assertItemExists;
		$this->removeStatement = $removeStatement;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( RemoveItemStatementRequest $request ): void {
		$removeStatementRequest = new RemoveStatementRequest(
			$request->getStatementId(),
			$request->getEditTags(),
			$request->isBot(),
			$request->getComment(),
			$request->getUsername()
		);
		$this->removeStatement->assertValidRequest( $removeStatementRequest );

		// TODO: remove try catch block after adding proper validation
		try {
			$itemId = new ItemId( $request->getItemId() );
		} catch ( InvalidArgumentException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				"Not a valid item ID: {$request->getItemId()}"
			);
		}

		$this->assertItemExists->execute( $itemId );

		if ( strpos( $request->getStatementId(), $request->getItemId() . StatementGuid::SEPARATOR ) !== 0 ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$request->getStatementId()}"
			);
		}

		$this->removeStatement->execute( $removeStatementRequest );
	}

}
