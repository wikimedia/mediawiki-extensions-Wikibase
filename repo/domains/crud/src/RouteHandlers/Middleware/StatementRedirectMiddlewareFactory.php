<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\StatementSubjectRetriever;

/**
 * @license GPL-2.0-or-later
 */
class StatementRedirectMiddlewareFactory {

	private StatementSubjectRetriever $statementSubjectRetriever;
	private EntityIdParser $entityIdParser;

	public function __construct(
		EntityIdParser $entityIdParser,
		StatementSubjectRetriever $statementSubjectRetriever
	) {
		$this->entityIdParser = $entityIdParser;
		$this->statementSubjectRetriever = $statementSubjectRetriever;
	}

	public function newStatementRedirectMiddleware(
		string $statementIdPathParameter,
		?string $subjectIdPathParameter = null
	): StatementRedirectMiddleware {
		return new StatementRedirectMiddleware(
			$this->entityIdParser,
			$this->statementSubjectRetriever,
			$statementIdPathParameter,
			$subjectIdPathParameter
		);
	}

}
