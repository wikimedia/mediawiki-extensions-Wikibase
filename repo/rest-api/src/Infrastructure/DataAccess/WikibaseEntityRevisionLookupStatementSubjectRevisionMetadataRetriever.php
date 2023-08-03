<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestStatementSubjectRevisionMetadataResult as MetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\StatementSubjectRevisionMetaDataRetriever;

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
			->onConcreteRevision( fn( $id, $timestamp ) => MetadataResult::concreteRevision( $id, $timestamp ) )
			->onRedirect( fn( int $revId, ItemId $redirectTarget ) => MetadataResult::redirect( $redirectTarget ) )
			->onNonexistentEntity( fn() => MetadataResult::subjectNotFound() )
			->map();
	}

}
