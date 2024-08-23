<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class PermissionCheckResult {

	private const DENIAL_REASON_UNKNOWN = 0;

	private ?int $denialReason;

	private function __construct( ?int $denialReason ) {
		$this->denialReason = $denialReason;
	}

	public static function newAllowed(): self {
		return new self( null );
	}

	public static function newDenialForUnknownReason(): self {
		return new self( self::DENIAL_REASON_UNKNOWN );
	}

	public function isDenied(): bool {
		return $this->denialReason !== null;
	}

}
