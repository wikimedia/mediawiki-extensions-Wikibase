<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatement {

	private GetPropertyStatementValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private GetStatement $getStatement;

	public function __construct(
		GetPropertyStatementValidator $validator,
		AssertPropertyExists $assertPropertyExists,
		GetStatement $getStatement
	) {
		$this->validator = $validator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->getStatement = $getStatement;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyStatementRequest $request ): GetStatementResponse {
		$this->validator->assertValidRequest( $request );
		$getStatementRequest = new GetStatementRequest( $request->getStatementId() );
		$this->getStatement->assertValidRequest( $getStatementRequest );

		$this->assertPropertyExists->execute( new NumericPropertyId( $request->getPropertyId() ) );

		if ( strpos( $request->getStatementId(), $request->getPropertyId() . StatementGuid::SEPARATOR ) !== 0 ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$request->getStatementId()}"
			);
		}

		return $this->getStatement->execute( $getStatementRequest );
	}

}
