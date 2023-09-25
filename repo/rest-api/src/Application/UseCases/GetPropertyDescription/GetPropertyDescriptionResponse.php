<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription;

use Wikibase\Repo\RestApi\Domain\ReadModel\Description;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionResponse {

	private Description $description;

	public function __construct( Description $description ) {
		$this->description = $description;
	}

	public function getDescription(): Description {
		return $this->description;
	}

}
