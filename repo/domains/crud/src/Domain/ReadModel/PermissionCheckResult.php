<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class PermissionCheckResult {

	public const DENIAL_REASON_UNKNOWN = 0;
	public const DENIAL_REASON_PAGE_PROTECTED = 1;
	public const DENIAL_REASON_USER_BLOCKED = 2;
	public const DENIAL_REASON_IP_BLOCKED = 3;

	private ?int $denialReason;

	private function __construct( ?int $denialReason ) {
		$this->denialReason = $denialReason;
	}

	public static function newAllowed(): self {
		return new self( null );
	}

	public static function newPageProtected(): self {
		return new self( self::DENIAL_REASON_PAGE_PROTECTED );
	}

	public static function newUserBlocked(): self {
		return new self( self::DENIAL_REASON_USER_BLOCKED );
	}

	public static function newIpBlocked(): self {
		return new self( self::DENIAL_REASON_IP_BLOCKED );
	}

	public static function newDenialForUnknownReason(): self {
		return new self( self::DENIAL_REASON_UNKNOWN );
	}

	public function isDenied(): bool {
		return $this->denialReason !== null;
	}

	public function getDenialReason(): ?int {
		return $this->denialReason;
	}

}
