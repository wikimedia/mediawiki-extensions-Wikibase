<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;

/**
 * @license GPL-2.0-or-later
 */
interface StatementRemover {

	public function remove( StatementGuid $statementGuid, EditMetadata $editMetadata ): void;

}
