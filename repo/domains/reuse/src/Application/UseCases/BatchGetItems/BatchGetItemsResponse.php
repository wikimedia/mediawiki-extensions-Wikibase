<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems;

use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemsResponse {

	public function __construct( public readonly ItemsBatch $itemsBatch ) {
	}

}
