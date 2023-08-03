<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementRevision;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\EntityUpdateFailed;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\StatementUpdateFailed;

/**
 * @license GPL-2.0-or-later
 */
interface StatementUpdater {

	/**
	 * @throws EntityUpdateFailed
	 * @throws StatementUpdateFailed
	 */
	public function update( Statement $statement, EditMetadata $editMetadata ): StatementRevision;

}
