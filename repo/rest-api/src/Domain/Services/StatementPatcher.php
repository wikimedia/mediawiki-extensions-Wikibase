<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
interface StatementPatcher {

	public function patchStatement( Statement $statement, array $patch ): Statement;

}
