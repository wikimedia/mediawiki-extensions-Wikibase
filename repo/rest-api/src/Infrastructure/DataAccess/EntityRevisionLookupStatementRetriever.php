<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupStatementRetriever implements StatementRetriever {

	private StatementSubjectRetriever $statementSubjectRetriever;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct(
		StatementSubjectRetriever $statementSubjectRetriever,
		StatementReadModelConverter $statementReadModelConverter
	) {
		$this->statementSubjectRetriever = $statementSubjectRetriever;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function getStatement( StatementGuid $statementId ): ?Statement {
		$subjectId = $statementId->getEntityId();
		$subject = $this->statementSubjectRetriever->getStatementSubject( $subjectId );

		if ( $subject === null ) {
			return null;
		}

		$statement = $subject->getStatements()->getFirstStatementWithGuid( (string)$statementId );
		return $statement ? $this->statementReadModelConverter->convert( $statement ) : null;
	}

}
