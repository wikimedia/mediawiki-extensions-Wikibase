<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatement {

	private PatchItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private PatchStatement $patchStatement;

	public function __construct(
		PatchItemStatementValidator $validator,
		AssertItemExists $assertItemExists,
		PatchStatement $patchStatement
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->patchStatement = $patchStatement;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( PatchItemStatementRequest $request ): PatchStatementResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$this->assertItemExists->execute( $deserializedRequest->getItemId() );

		if ( !$deserializedRequest->getStatementId()->getEntityId()->equals( $deserializedRequest->getItemId() ) ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$deserializedRequest->getStatementId()}"
			);
		}

		return $this->patchStatement->execute( $request );
	}

}
