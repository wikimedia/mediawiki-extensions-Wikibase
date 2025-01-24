<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\StatementEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementRemover;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class RemoveStatement {
	use UpdateExceptionHandler;

	private RemoveStatementValidator $validator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private StatementWriteModelRetriever $statementRetriever;
	private StatementRemover $statementRemover;

	public function __construct(
		RemoveStatementValidator $validator,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		AssertStatementSubjectExists $assertStatementSubjectExists,
		StatementWriteModelRetriever $statementRetriever,
		StatementRemover $statementRemover
	) {
		$this->validator = $validator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->assertStatementSubjectExists = $assertStatementSubjectExists;
		$this->statementRetriever = $statementRetriever;
		$this->statementRemover = $statementRemover;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( RemoveStatementRequest $request ): void {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$this->assertStatementSubjectExists->execute( $deserializedRequest->getStatementId() );

		$this->assertUserIsAuthorized->checkEditPermissions(
			$deserializedRequest->getStatementId()->getEntityId(),
			$deserializedRequest->getEditMetadata()->getUser()
		);

		$statementToRemove = $this->statementRetriever->getStatementWriteModel( $deserializedRequest->getStatementId() );
		if ( !$statementToRemove ) {
			throw UseCaseError::newResourceNotFound( 'statement' );
		}

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			StatementEditSummary::newRemoveSummary( $deserializedRequest->getEditMetadata()->getComment(), $statementToRemove )
		);

		$this->executeWithExceptionHandling(
			fn() => $this->statementRemover->remove( $deserializedRequest->getStatementId(), $editMetadata )
		);
	}

}
