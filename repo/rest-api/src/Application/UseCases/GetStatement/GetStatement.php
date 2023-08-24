<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetStatement {

	private StatementRetriever $statementRetriever;
	private GetStatementValidator $validator;
	private GetLatestStatementSubjectRevisionMetadata $getRevisionMetadata;

	public function __construct(
		GetStatementValidator $validator,
		StatementRetriever $statementRetriever,
		GetLatestStatementSubjectRevisionMetadata $getRevisionMetadata
	) {
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->statementRetriever = $statementRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetStatementRequest $statementRequest ): GetStatementResponse {
		$this->assertValidRequest( $statementRequest );

		$statementIdParser = new StatementGuidParser( new BasicEntityIdParser() );
		$statementId = $statementIdParser->parse( $statementRequest->getStatementId() );

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $statementId->getEntityId() );

		$statement = $this->statementRetriever->getStatement( $statementId );
		if ( !$statement ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: $statementId"
			);
		}

		return new GetStatementResponse( $statement, $lastModified, $revisionId );
	}

	public function assertValidRequest( GetStatementRequest $request ): void {
		$this->validator->assertValidRequest( $request );
	}
}
