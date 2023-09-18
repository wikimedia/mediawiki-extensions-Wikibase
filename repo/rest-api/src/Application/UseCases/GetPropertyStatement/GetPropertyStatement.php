<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
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
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$this->assertPropertyExists->execute( $deserializedRequest->getPropertyId() );

		if ( !$deserializedRequest->getStatementId()->getEntityId()->equals( $deserializedRequest->getPropertyId() ) ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$deserializedRequest->getStatementId()}"
			);
		}

		return $this->getStatement->execute( $request );
	}

}
