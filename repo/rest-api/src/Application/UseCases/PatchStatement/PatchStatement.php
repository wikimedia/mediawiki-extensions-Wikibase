<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchStatement;

use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchStatement {

	private PatchStatementValidator $useCaseValidator;
	private PatchedStatementValidator $patchedStatementValidator;
	private JsonPatcher $jsonPatcher;
	private StatementSerializer $statementSerializer;
	private StatementGuidParser $statementIdParser;
	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private StatementRetriever $statementRetriever;
	private StatementUpdater $statementUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		PatchStatementValidator $useCaseValidator,
		PatchedStatementValidator $patchedStatementValidator,
		JsonPatcher $jsonPatcher,
		StatementSerializer $statementSerializer,
		StatementGuidParser $statementIdParser,
		AssertStatementSubjectExists $assertStatementSubjectExists,
		StatementRetriever $statementRetriever,
		StatementUpdater $statementUpdater,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->patchedStatementValidator = $patchedStatementValidator;
		$this->statementSerializer = $statementSerializer;
		$this->statementRetriever = $statementRetriever;
		$this->jsonPatcher = $jsonPatcher;
		$this->statementIdParser = $statementIdParser;
		$this->assertStatementSubjectExists = $assertStatementSubjectExists;
		$this->statementUpdater = $statementUpdater;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( PatchStatementRequest $request ): PatchStatementResponse {
		$this->assertValidRequest( $request );

		$statementId = $this->statementIdParser->parse( $request->getStatementId() );

		$this->assertStatementSubjectExists->execute( $statementId );

		$statementToPatch = $this->statementRetriever->getStatement( $statementId );

		if ( !$statementToPatch ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: $statementId"
			);
		}

		$this->assertUserIsAuthorized->execute( $statementId->getEntityId(), $request->getUsername() );

		$serialization = $this->statementSerializer->serialize( $statementToPatch );

		try {
			$patchedSerialization = $this->jsonPatcher->patch( $serialization, $request->getPatch() );
		} catch ( PatchPathException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCH_TARGET_NOT_FOUND,
				"Target '{$e->getOperation()[$e->getField()]}' not found on the resource",
				[ UseCaseError::CONTEXT_OPERATION => $e->getOperation(), UseCaseError::CONTEXT_FIELD => $e->getField() ]
			);
		} catch ( PatchTestConditionFailedException $e ) {
			$operation = $e->getOperation();
			throw new UseCaseError(
				UseCaseError::PATCH_TEST_FAILED,
				'Test operation in the provided patch failed. ' .
				"At path '" . $operation['path'] .
				"' expected '" . json_encode( $operation['value'] ) .
				"', actual: '" . json_encode( $e->getActualValue() ) . "'",
				[ UseCaseError::CONTEXT_OPERATION => $operation, UseCaseError::CONTEXT_ACTUAL_VALUE => $e->getActualValue() ]
			);
		}

		$patchedStatement = $this->patchedStatementValidator->validateAndDeserializeStatement( $patchedSerialization );

		if ( $patchedStatement->getGuid() === null ) {
			$patchedStatement->setGuid( $request->getStatementId() );
		} elseif ( $patchedStatement->getGuid() !== $request->getStatementId() ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				'Cannot change the ID of the existing statement'
			);
		}

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newPatchSummary( $request->getComment(), $patchedStatement )
		);

		try {
			$newRevision = $this->statementUpdater->update( $patchedStatement, $editMetadata );
		} catch ( PropertyChangedException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		}

		return new PatchStatementResponse( $newRevision->getStatement(), $newRevision->getLastModified(), $newRevision->getRevisionId() );
	}

	public function assertValidRequest( PatchStatementRequest $request ): void {
		$this->useCaseValidator->assertValidRequest( $request );
	}

}
