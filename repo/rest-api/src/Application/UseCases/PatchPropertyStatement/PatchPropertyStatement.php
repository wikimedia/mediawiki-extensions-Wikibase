<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyStatement {

	private PatchPropertyStatementValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private PatchStatement $patchStatement;

	public function __construct(
		PatchPropertyStatementValidator $validator,
		AssertPropertyExists $assertPropertyExists,
		PatchStatement $patchStatement
	) {
		$this->validator = $validator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->patchStatement = $patchStatement;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchPropertyStatementRequest $request ): PatchStatementResponse {
		$this->validator->assertValidRequest( $request );
		$patchStatementRequest = new PatchStatementRequest(
			$request->getStatementId(),
			$request->getPatch(),
			$request->getEditTags(),
			$request->isBot(),
			$request->getComment(),
			$request->getUsername()
		);
		$this->patchStatement->assertValidRequest( $patchStatementRequest );
		$this->assertPropertyExists->execute( new NumericPropertyId( $request->getPropertyId() ) );

		if ( strpos( $request->getStatementId(), $request->getPropertyId() . StatementGuid::SEPARATOR ) !== 0 ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$request->getStatementId()}"
			);
		}

		return $this->patchStatement->execute( $patchStatementRequest );
	}

}
