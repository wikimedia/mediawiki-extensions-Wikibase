<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatement {

	private GetItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private GetStatement $getStatement;

	public function __construct(
		GetItemStatementValidator $validator,
		AssertItemExists $assertItemExists,
		GetStatement $getStatement
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->getStatement = $getStatement;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetItemStatementRequest $request ): GetStatementResponse {
		$this->validator->assertValidRequest( $request );
		$getStatementRequest = new GetStatementRequest( $request->getStatementId() );
		$this->getStatement->assertValidRequest( $getStatementRequest );

		$this->assertItemExists->execute( new ItemId( $request->getItemId() ) );

		if ( strpos( $request->getStatementId(), $request->getItemId() . StatementGuid::SEPARATOR ) !== 0 ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$request->getStatementId()}"
			);
		}

		return $this->getStatement->execute( $getStatementRequest );
	}

}
