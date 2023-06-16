<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupStatementRetriever implements StatementRetriever {

	private EntityRevisionLookup $entityRevisionLookup;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		StatementReadModelConverter $statementReadModelConverter
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function getStatement( StatementGuid $statementId ): ?Statement {
		$subjectId = $statementId->getEntityId();
		$subject = $this->getStatementSubject( $subjectId );

		if ( $subject === null ) {
			return null;
		}

		$statement = $subject->getStatements()->getFirstStatementWithGuid( (string)$statementId );
		return $statement ? $this->statementReadModelConverter->convert( $statement ) : null;
	}

	private function getStatementSubject( EntityId $subjectId ): ?StatementListProvidingEntity {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $subjectId );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			return null;
		}

		if ( !$entityRevision ) {
			return null;
		}

		$subject = $entityRevision->getEntity();
		if ( !$subject instanceof StatementListProvidingEntity ) {
			return null;
		}

		return $subject;
	}

}
