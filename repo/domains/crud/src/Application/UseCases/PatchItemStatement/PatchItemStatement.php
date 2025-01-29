<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

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

		return $this->patchStatement->execute( $request );
	}

}
