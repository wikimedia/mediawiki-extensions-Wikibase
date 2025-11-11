<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\LatestStatementSubjectRevisionMetadataResult as MetadataResult;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementSubjectRevisionMetaDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever implements StatementSubjectRevisionMetaDataRetriever {

	private EntityRevisionLookup $revisionLookup;

	public function __construct( EntityRevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	public function getLatestRevisionMetadata( StatementGuid $statementId ): MetadataResult {
		return $this->revisionLookup->getLatestRevisionId( $statementId->getEntityId() )
			->onConcreteRevision( MetadataResult::concreteRevision( ... ) )
			->onRedirect( fn( int $revId, ItemId $redirectTarget ) => MetadataResult::redirect( $redirectTarget ) )
			->onNonexistentEntity( MetadataResult::subjectNotFound( ... ) )
			->map();
	}

}
