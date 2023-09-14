<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
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
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$this->assertPropertyExists->execute( $deserializedRequest->getPropertyId() );

		if ( !$deserializedRequest->getStatementId()->getEntityId()->equals( $deserializedRequest->getPropertyId() ) ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$deserializedRequest->getStatementId()}"
			);
		}

		return $this->patchStatement->execute( $request );
	}

}
