<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class User {

	private ?string $username;

	public static function newAnonymous(): self {
		return new self( null );
	}

	public static function withUsername( string $username ): self {
		return new self( $username );
	}

	private function __construct( ?string $username ) {
		$this->username = $username;
	}

	/**
	 * @return string|null Returns null for anonymous user
	 */
	public function getUsername(): ?string {
		return $this->username;
	}

	public function isAnonymous(): bool {
		return $this->username === null;
	}

}
