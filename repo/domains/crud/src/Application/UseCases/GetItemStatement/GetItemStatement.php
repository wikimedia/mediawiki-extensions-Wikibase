<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
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
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$this->assertItemExists->execute( $deserializedRequest->getItemId() );

		return $this->getStatement->execute( $request );
	}

}
