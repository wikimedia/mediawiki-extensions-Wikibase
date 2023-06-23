<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
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

	public function getLatestRevisionMetadata( EntityId $subjectId ): MetadataResult {
		return $this->revisionLookup->getLatestRevisionId( $subjectId )
			->onConcreteRevision( fn ( $id, $timestamp ) => MetadataResult::concreteRevision( $id, $timestamp ) )
			->onRedirect( fn ( int $revId, ItemId $redirectTarget ) => MetadataResult::redirect( $redirectTarget ) )
			->onNonexistentEntity( fn () => MetadataResult::subjectNotFound() )
			->map();
	}
}
