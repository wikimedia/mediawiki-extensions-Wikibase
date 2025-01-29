<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

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

		return $this->patchStatement->execute( $request );
	}

}
