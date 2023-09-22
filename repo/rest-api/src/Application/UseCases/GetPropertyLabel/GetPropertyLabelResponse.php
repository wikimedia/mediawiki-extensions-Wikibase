<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel;

use Wikibase\Repo\RestApi\Domain\ReadModel\Label;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelResponse {

	private Label $label;

	public function __construct( Label $label ) {
		$this->label = $label;
	}

	public function getLabel(): Label {
		return $this->label;
	}

}
