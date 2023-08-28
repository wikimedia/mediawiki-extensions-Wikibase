<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement;

use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementNotFoundException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceStatement {

	private ReplaceStatementValidator $validator;
	private StatementGuidParser $statementIdParser;
	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private StatementUpdater $statementUpdater;

	public function __construct(
		ReplaceStatementValidator $validator,
		StatementGuidParser $statementIdParser,
		AssertStatementSubjectExists $assertStatementSubjectExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		StatementUpdater $statementUpdater
	) {
		$this->validator = $validator;
		$this->statementIdParser = $statementIdParser;
		$this->assertStatementSubjectExists = $assertStatementSubjectExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->statementUpdater = $statementUpdater;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( ReplaceStatementRequest $request ): ReplaceStatementResponse {
		$this->assertValidRequest( $request );

		$statementId = $this->statementIdParser->parse( $request->getStatementId() );
		$this->assertStatementSubjectExists->execute( $statementId );
		$this->assertUserIsAuthorized->execute( $statementId->getEntityId(), $request->getUsername() );

		$statement = $this->validator->getValidatedStatement();
		if ( $statement->getGuid() === null ) {
			$statement->setGuid( $request->getStatementId() );
		} elseif ( $statement->getGuid() !== $request->getStatementId() ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				'Cannot change the ID of the existing statement'
			);
		}

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			StatementEditSummary::newReplaceSummary( $request->getComment(), $statement )
		);

		try {
			$newRevision = $this->statementUpdater->update( $statement, $editMetadata );
		} catch ( StatementNotFoundException $e ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: $statementId"
			);
		} catch ( PropertyChangedException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY,
				'Cannot change the property of the existing statement'
			);
		}

		return new ReplaceStatementResponse(
			$newRevision->getStatement(),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

	public function assertValidRequest( ReplaceStatementRequest $request ): void {
		$this->validator->assertValidRequest( $request );
	}

}
