<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

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

		if ( !$deserializedRequest->getStatementId()->getEntityId()->equals( $deserializedRequest->getPropertyId() ) ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: {$deserializedRequest->getStatementId()}"
			);
		}

		$this->removeStatement->execute(
			new RemoveStatementRequest(
				(string)$deserializedRequest->getStatementId(),
				$deserializedRequest->getEditMetadata()->getTags(),
				$deserializedRequest->getEditMetadata()->isBot(),
				$deserializedRequest->getEditMetadata()->getComment(),
				$deserializedRequest->getEditMetadata()->getUser()->getUsername()
			)
		);
	}

}
