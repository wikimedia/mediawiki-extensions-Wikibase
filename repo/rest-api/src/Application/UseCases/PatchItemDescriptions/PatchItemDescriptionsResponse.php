<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions;

use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptionsResponse {

	private Descriptions $descriptions;

	public function __construct( Descriptions $descriptions ) {
		$this->descriptions = $descriptions;
	}

	public function getDescriptions(): Descriptions {
		return $this->descriptions;
	}

}
