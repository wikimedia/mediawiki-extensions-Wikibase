<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\StatementRemover;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\EntityUpdateFailed;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\StatementSubjectDisappeared;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterStatementRemover implements StatementRemover {

	private StatementSubjectRetriever $statementSubjectRetriever;
	private EntityUpdater $entityUpdater;

	public function __construct( StatementSubjectRetriever $statementSubjectRetriever, EntityUpdater $entityUpdater ) {
		$this->statementSubjectRetriever = $statementSubjectRetriever;
		$this->entityUpdater = $entityUpdater;
	}

	/**
	 * @throws StatementSubjectDisappeared
	 * @throws EntityUpdateFailed
	 */
	public function remove( StatementGuid $statementGuid, EditMetadata $editMetadata ): void {

		$subject = $this->statementSubjectRetriever->getStatementSubject( $statementGuid->getEntityId() );

		// if $subject is null, then it went missing between the use case
		// subject assertion and invoking the statement remover by the use case
		if ( $subject === null ) {
			throw new StatementSubjectDisappeared();
		}

		$subject->getStatements()->removeStatementsWithGuid( (string)$statementGuid );

		$this->entityUpdater->update( $subject, $editMetadata );
	}

}
