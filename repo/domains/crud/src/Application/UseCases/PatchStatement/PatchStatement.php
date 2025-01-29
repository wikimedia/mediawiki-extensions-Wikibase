<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement;

use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\StatementEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchStatement {

	use UpdateExceptionHandler;

	private PatchStatementValidator $useCaseValidator;
	private PatchedStatementValidator $patchedStatementValidator;
	private PatchJson $jsonPatcher;
	private StatementSerializer $statementSerializer;
	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private StatementRetriever $statementRetriever;
	private StatementUpdater $statementUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		PatchStatementValidator $useCaseValidator,
		PatchedStatementValidator $patchedStatementValidator,
		PatchJson $jsonPatcher,
		StatementSerializer $statementSerializer,
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
		$this->assertStatementSubjectExists = $assertStatementSubjectExists;
		$this->statementUpdater = $statementUpdater;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( PatchStatementRequest $request ): PatchStatementResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$statementId = $deserializedRequest->getStatementId();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertStatementSubjectExists->execute( $statementId );

		$statementToPatch = $this->statementRetriever->getStatement( $statementId );

		if ( !$statementToPatch ) {
			throw UseCaseError::newResourceNotFound( 'statement' );
		}

		$this->assertUserIsAuthorized->checkEditPermissions( $statementId->getEntityId(), $editMetadata->getUser() );

		$serialization = $this->statementSerializer->serialize( $statementToPatch );

		$patchedSerialization = $this->jsonPatcher->execute( $serialization, $deserializedRequest->getPatch() );

		$patchedStatement = $this->patchedStatementValidator->validateAndDeserializeStatement( $patchedSerialization );

		if ( $patchedStatement->getGuid() === null ) {
			$patchedStatement->setGuid( (string)$deserializedRequest->getStatementId() );
		} elseif ( $patchedStatement->getGuid() !== (string)$deserializedRequest->getStatementId() ) {
			throw UseCaseError::newPatchResultModifiedReadOnlyValue( '/id' );
		}

		try {
			$newRevision = $this->executeWithExceptionHandling( fn() => $this->statementUpdater->update(
				$patchedStatement,
				new EditMetadata(
					$editMetadata->getTags(),
					$editMetadata->isBot(),
					StatementEditSummary::newPatchSummary( $editMetadata->getComment(), $patchedStatement )
				)
			) );
		} catch ( PropertyChangedException $e ) {
			throw UseCaseError::newPatchResultModifiedReadOnlyValue( '/property/id' );
		}

		return new PatchStatementResponse( $newRevision->getStatement(), $newRevision->getLastModified(), $newRevision->getRevisionId() );
	}

}
