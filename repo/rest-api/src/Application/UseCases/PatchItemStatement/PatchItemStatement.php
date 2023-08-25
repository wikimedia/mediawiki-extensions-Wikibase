<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;
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
		$this->assertItemExists->execute( new ItemId( $request->getItemId() ) );

		if ( strpos( $request->getStatementId(), $request->getItemId() . StatementGuid::SEPARATOR ) !== 0 ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$request->getStatementId()}"
			);
		}

		return $this->patchStatement->execute( $patchStatementRequest );
	}

}
