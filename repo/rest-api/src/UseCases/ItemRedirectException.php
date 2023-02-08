<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
class ItemRedirectException extends RuntimeException {
	private string $redirectTargetId;

	public function __construct( string $redirectTargetId ) {
		parent::__construct();
		$this->redirectTargetId = $redirectTargetId;
	}

	public function getRedirectTargetId(): string {
		return $this->redirectTargetId;
	}
}
