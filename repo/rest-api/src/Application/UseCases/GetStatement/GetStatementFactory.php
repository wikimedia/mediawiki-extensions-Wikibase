<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\Validation\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementFactory {

	private StatementIdValidator $statementIdValidator;
	private StatementRetriever $statementRetriever;
	private GetLatestStatementSubjectRevisionMetadata $getLatestStatementSubjectRevisionMetadata;

	public function __construct(
		StatementIdValidator $statementIdValidator,
		StatementRetriever $statementRetriever,
		GetLatestStatementSubjectRevisionMetadata $getLatestStatementSubjectRevisionMetadata
	) {
		$this->statementIdValidator = $statementIdValidator;
		$this->statementRetriever = $statementRetriever;
		$this->getLatestStatementSubjectRevisionMetadata = $getLatestStatementSubjectRevisionMetadata;
	}

	public function newGetStatement( RequestedSubjectIdValidator $requestedSubjectIdValidator ): GetStatement {
		return new GetStatement(
			new GetStatementValidator( $this->statementIdValidator, $requestedSubjectIdValidator ),
			$this->statementRetriever,
			$this->getLatestStatementSubjectRevisionMetadata
		);
	}

}
