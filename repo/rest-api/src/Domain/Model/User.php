<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class User {

	private $usernameOrIp;
	private $isAnonymous;

	public function __construct( string $usernameOrIp, bool $isAnonymous = false ) {
		$this->usernameOrIp = $usernameOrIp;
		$this->isAnonymous = $isAnonymous;
	}

	public function getUsernameOrIp(): string {
		return $this->usernameOrIp;
	}

	public function isAnonymous(): bool {
		return $this->isAnonymous;
	}

}
