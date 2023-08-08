<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
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
		$requestedSubjectId = $statementRequest->getSubjectId();

		/** @var EntityId $subjectId */
		$subjectId = $requestedSubjectId ? ( new BasicEntityIdParser() )->parse( $requestedSubjectId ) : $statementId->getEntityId();
		'@phan-var EntityId $subjectId';

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $subjectId );

		if ( $requestedSubjectId && $requestedSubjectId !== (string)$statementId->getEntityId() ) {
			$this->throwStatementNotFoundException( $statementRequest->getStatementId() );
		}

		$statement = $this->statementRetriever->getStatement( $statementId );
		if ( !$statement ) {
			$this->throwStatementNotFoundException( $statementRequest->getStatementId() );
		}

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		return new GetStatementResponse( $statement, $lastModified, $revisionId );
	}

	/**
	 * @return never
	 * @throws UseCaseError
	 */
	private function throwStatementNotFoundException( string $statementId ): void {
		throw new UseCaseError(
			UseCaseError::STATEMENT_NOT_FOUND,
			"Could not find a statement with the ID: $statementId"
		);
	}

	public function assertValidRequest( GetStatementRequest $request ): void {
		$this->validator->assertValidRequest( $request );
	}
}
