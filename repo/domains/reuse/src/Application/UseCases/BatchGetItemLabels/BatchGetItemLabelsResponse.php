<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels;

use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemLabelsResponse {

	public function __construct( public readonly ItemLabelsBatch $batch ) {
	}

}
