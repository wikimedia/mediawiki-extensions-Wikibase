<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatement {

	private ReplaceItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private ReplaceStatement $replaceStatement;

	public function __construct(
		ReplaceItemStatementValidator $validator,
		AssertItemExists $assertItemExists,
		ReplaceStatement $replaceStatement
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->replaceStatement = $replaceStatement;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( ReplaceItemStatementRequest $request ): ReplaceStatementResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$this->assertItemExists->execute( $deserializedRequest->getItemId() );

		if ( !$deserializedRequest->getStatementId()->getEntityId()->equals( $deserializedRequest->getItemId() ) ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$deserializedRequest->getStatementId()}"
			);
		}

		return $this->replaceStatement->execute( $request );
	}
}
