<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

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

		return $this->getStatement->execute( $request );
	}

}
