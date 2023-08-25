<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ReplacePropertyStatement {

	private ReplacePropertyStatementValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private ReplaceStatement $replaceStatement;

	public function __construct(
		ReplacePropertyStatementValidator $validator,
		AssertPropertyExists $assertPropertyExists,
		ReplaceStatement $replaceStatement
	) {
		$this->validator = $validator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->replaceStatement = $replaceStatement;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( ReplacePropertyStatementRequest $request ): ReplaceStatementResponse {
		$this->validator->assertValidRequest( $request );
		$replaceStatementRequest = $this->createReplaceStatementRequest( $request );
		$this->replaceStatement->assertValidRequest( $replaceStatementRequest );

		$this->assertPropertyExists->execute( new NumericPropertyId( $request->getPropertyId() ) );

		if ( strpos( $request->getStatementId(), $request->getPropertyId() . StatementGuid::SEPARATOR ) !== 0 ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$request->getStatementId()}"
			);
		}

		return $this->replaceStatement->execute( $replaceStatementRequest );
	}

	public function createReplaceStatementRequest( ReplacePropertyStatementRequest $request ): ReplaceStatementRequest {
		return new ReplaceStatementRequest(
			$request->getStatementId(),
			$request->getStatement(),
			$request->getEditTags(),
			$request->isBot(),
			$request->getComment(),
			$request->getUsername()
		);
	}

}
