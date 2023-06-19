<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestStatementSubjectRevisionMetadataResult;

/**
 * @license GPL-2.0-or-later
 */
interface StatementSubjectRevisionMetaDataRetriever {

	public function getLatestRevisionMetadata( StatementGuid $statementId ): LatestStatementSubjectRevisionMetadataResult;

}
