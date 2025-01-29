<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
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
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$this->assertPropertyExists->execute( $deserializedRequest->getPropertyId() );

		return $this->replaceStatement->execute( $request );
	}

}
