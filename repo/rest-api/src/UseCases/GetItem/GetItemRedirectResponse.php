<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRedirectResponse {

	private $redirectTargetId;

	public function __construct( string $redirectTargetId ) {
		$this->redirectTargetId = $redirectTargetId;
	}

	public function getRedirectTargetId(): string {
		return $this->redirectTargetId;
	}

}
