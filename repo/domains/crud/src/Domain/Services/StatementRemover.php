<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;

/**
 * @license GPL-2.0-or-later
 */
interface StatementRemover {

	public function remove( StatementGuid $statementGuid, EditMetadata $editMetadata ): void;

}
