<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

/**
 * @license GPL-2.0-or-later
 */
class ItemRedirectResponse {

	private string $redirectTargetId;

	public function __construct( string $redirectTargetId ) {
		$this->redirectTargetId = $redirectTargetId;
	}

	public function getRedirectTargetId(): string {
		return $this->redirectTargetId;
	}

}
