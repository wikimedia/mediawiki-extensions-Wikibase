<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class RemovePropertyStatement {

	private AssertPropertyExists $assertPropertyExists;
	private RemoveStatement $removeStatement;
	private RemovePropertyStatementValidator $validator;

	public function __construct(
		AssertPropertyExists $assertPropertyExists,
		RemoveStatement $removeStatement,
		RemovePropertyStatementValidator $validator
	) {
		$this->assertPropertyExists = $assertPropertyExists;
		$this->removeStatement = $removeStatement;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( RemovePropertyStatementRequest $request ): void {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$this->assertPropertyExists->execute( $deserializedRequest->getPropertyId() );

		$this->removeStatement->execute( $request );
	}

}
