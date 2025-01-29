<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\LatestStatementSubjectRevisionMetadataResult;

/**
 * @license GPL-2.0-or-later
 */
interface StatementSubjectRevisionMetaDataRetriever {

	public function getLatestRevisionMetadata( StatementGuid $statementId ): LatestStatementSubjectRevisionMetadataResult;

}
