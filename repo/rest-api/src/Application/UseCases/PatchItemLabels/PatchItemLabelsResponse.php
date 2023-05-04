<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsResponse {

	private Labels $labels;

	public function __construct( Labels $labels ) {
		$this->labels = $labels;
	}

	public function getLabels(): Labels {
		return $this->labels;
	}

}
