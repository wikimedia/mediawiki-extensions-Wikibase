<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

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
	public function execute( GetStatementRequest $request ): GetStatementResponse {
		$statementId = $this->validator->validateAndDeserialize( $request )->getStatementId();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $statementId );

		$statement = $this->statementRetriever->getStatement( $statementId );
		if ( !$statement ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: $statementId"
			);
		}

		return new GetStatementResponse( $statement, $lastModified, $revisionId );
	}

	// will be obsolete once T344994 is done
	public function assertValidRequest( GetStatementRequest $request ): DeserializedGetStatementRequest {
		return $this->validator->validateAndDeserialize( $request );
	}

}
