<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementRevision;

/**
 * @license GPL-2.0-or-later
 */
interface StatementUpdater {

	public function update( Statement $statement, EditMetadata $editMetadata ): StatementRevision;

}
