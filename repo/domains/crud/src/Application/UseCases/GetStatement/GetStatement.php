<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementRetriever;

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
			throw UseCaseError::newResourceNotFound( 'statement' );
		}

		return new GetStatementResponse( $statement, $lastModified, $revisionId );
	}

}
