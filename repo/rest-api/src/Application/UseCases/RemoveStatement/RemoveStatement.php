<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement;

use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\StatementRemover;
use Wikibase\Repo\RestApi\Domain\Services\StatementWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class RemoveStatement {

	private RemoveStatementValidator $validator;
	private StatementGuidParser $statementIdParser;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private StatementWriteModelRetriever $statementRetriever;
	private StatementRemover $statementRemover;

	public function __construct(
		RemoveStatementValidator $validator,
		StatementGuidParser $statementGuidParser,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		AssertStatementSubjectExists $assertStatementSubjectExists,
		StatementWriteModelRetriever $statementRetriever,
		StatementRemover $statementRemover
	) {
		$this->validator = $validator;
		$this->statementIdParser = $statementGuidParser;
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
		$this->assertValidRequest( $request );

		$statementId = $this->statementIdParser->parse( $request->getStatementId() );

		$this->assertStatementSubjectExists->execute( $statementId );

		$this->assertUserIsAuthorized->execute( $statementId->getEntityId(), $request->getUsername() );

		$statementToRemove = $this->statementRetriever->getStatementWriteModel( $statementId );
		if ( !$statementToRemove ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: $statementId"
			);
		}

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newRemoveSummary( $request->getComment(), $statementToRemove )
		);

		$this->statementRemover->remove( $statementId, $editMetadata );
	}

	public function assertValidRequest( RemoveStatementRequest $request ): void {
		$this->validator->assertValidRequest( $request );
	}
}
