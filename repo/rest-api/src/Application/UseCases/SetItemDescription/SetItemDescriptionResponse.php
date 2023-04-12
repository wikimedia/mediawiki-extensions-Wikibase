<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionResponse {

	private string $description;

	public function __construct( string $description ) {
		$this->description = $description;
	}

	public function getDescription(): string {
		return $this->description;
	}
}
