<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;

/**
 * @license GPL-2.0-or-later
 */
class StatementSubjectRetriever {

	private EntityRevisionLookup $entityRevisionLookup;

	public function __construct( EntityRevisionLookup $entityRevisionLookup ) {
		$this->entityRevisionLookup = $entityRevisionLookup;
	}

	public function getStatementSubject( EntityId $subjectId ): ?StatementListProvidingEntity {
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
			throw new LogicException( 'Entity is not a ' . StatementListProvidingEntity::class );
		}

		return $subject;
	}

}
