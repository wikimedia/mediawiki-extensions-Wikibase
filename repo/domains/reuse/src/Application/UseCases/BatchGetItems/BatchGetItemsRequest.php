<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemsRequest {

	/**
	 * @param string[] $itemIds
	 */
	public function __construct( public readonly array $itemIds ) {
	}

}
