<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Actions;

use MediaWiki\Context\IContextSource;
use MediaWiki\User\UserIdentity;
use Wikibase\Repo\TempUserStatus;

/**
 * A Status representing the result of a {@link SubmitEntityAction} edit
 * (i.e. a revert or restore; see also {@link EditEntityStatus}).
 *
 * @inherits TempUserStatus<array{savedTempUser:?UserIdentity,context:IContextSource}>
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

}
