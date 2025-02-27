<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Actions;

use MediaWiki\Context\IContextSource;
use MediaWiki\User\UserIdentity;
use StatusValue;
use Wikibase\Repo\TempUserStatus;

/**
 * A Status representing the result of a {@link SubmitEntityAction} edit
 * (i.e. a revert or restore; see also {@link EditEntityStatus}).
 *
 * @license GPL-2.0-or-later
 */
class SubmitEntityStatus extends TempUserStatus {

	public static function newEdit(
		?UserIdentity $savedTempUser,
		IContextSource $context
	): self {
		return self::newTempUserStatus(
			[],
			$savedTempUser,
			$context
		);
	}

	/**
	 * @param StatusValue $sv
	 * @return static
	 */
	public static function wrap( $sv ) {
		// This implementation only exists to change the declared return type,
		// from Status to static (i.e. EditEntityStatus);
		// it would become redundant if Ic1a8eccc53 is merged.
		// (Note that the parent *implementation* already returns static,
		// it just isn’t declared as such yet.)
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return parent::wrap( $sv );
	}

}
