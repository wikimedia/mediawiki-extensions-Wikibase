<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
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
		$this->validator->assertValidRequest( $statementRequest );

		$statementIdParser = new StatementGuidParser( new ItemIdParser() );
		$statementId = $statementIdParser->parse( $statementRequest->getStatementId() );
		$requestedItemId = $statementRequest->getEntityId();
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $statementId );

		if ( !$itemId->equals( $statementId->getEntityId() ) ) {
			$this->throwStatementNotFoundException( $statementRequest->getStatementId() );
		}

		$statement = $this->statementRetriever->getStatement( $statementId );
		if ( !$statement ) {
			$this->throwStatementNotFoundException( $statementRequest->getStatementId() );
		}

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
}
