<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions;

use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemDescriptionsBatch;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemDescriptionsResponse {
	public function __construct( public readonly ItemDescriptionsBatch $batch ) {
	}
}
