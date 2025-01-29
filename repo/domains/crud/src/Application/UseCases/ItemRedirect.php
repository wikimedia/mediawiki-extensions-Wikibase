<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

/**
 * @license GPL-2.0-or-later
 */
class ItemRedirect extends UseCaseException {
	private string $redirectTargetId;

	public function __construct( string $redirectTargetId ) {
		parent::__construct();
		$this->redirectTargetId = $redirectTargetId;
	}

	public function getRedirectTargetId(): string {
		return $this->redirectTargetId;
	}
}
