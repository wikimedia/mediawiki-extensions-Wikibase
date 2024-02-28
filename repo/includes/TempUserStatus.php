<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use Wikimedia\Assert\Assert;

/**
 * A Status that may have resulted in a temporary user being created.
 *
 * On success, the value is an array that contains the saved temp user (if any),
 * a context that should be used for any further actions,
 * and arbitrary other data.
 *
 * @license GPL-2.0-or-later
 */
class TempUserStatus extends Status {

	/** @return static */ // TODO change next line to `static` and remove phpdoc in PHP 8.0
	protected static function newTempUserStatus(
		array $data,
		?UserIdentity $savedTempUser,
		IContextSource $context
	): self {
		return self::newGood( array_merge( $data, [
			'savedTempUser' => $savedTempUser,
			'context' => $context,
		] ) );
	}

	/**
	 * Get the temporary user that was created as part of the action that resulted in this status,
	 * or null if no temporary user was created.
	 * Only meaningful if the status is {@link self::isOK() OK}.
	 */
	public function getSavedTempUser(): ?UserIdentity {
		Assert::precondition( $this->isOK(), '$this->isOK()' );
		return $this->getValue()['savedTempUser'];
	}

	/**
	 * Get the context that should be used for any further actions.
	 * If a temporary user was created, then it will be set in this context.
	 * (The original context is usually not modified,
	 * so it may still reference the anonymous user instead of the temporary user.)
	 * Only meaningful if the status is {@link self::isOK() OK}.
	 */
	public function getContext(): IContextSource {
		Assert::precondition( $this->isOK(), '$this->isOK()' );
		return $this->getValue()['context'];
	}

}
