<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels;

use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyLabelsBatch;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetPropertyLabelsResponse {

	public function __construct( public readonly PropertyLabelsBatch $batch ) {
	}

}
