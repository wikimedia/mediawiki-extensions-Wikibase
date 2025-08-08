<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use Wikimedia\Assert\Assert;

// @phan-file-suppress PhanGenericConstructorTypes -- this class has no constructor

/**
 * A Status that may have resulted in a temporary user being created.
 *
 * On success, the value is an array that contains the saved temp user (if any),
 * a context that should be used for any further actions,
 * and arbitrary other data.
 *
 * @template T Must be a subtype of array{savedTempUser:?UserIdentity,context:IContextSource}
 * @inherits Status<T>
 * @license GPL-2.0-or-later
 */
class TempUserStatus extends Status {

	protected static function newTempUserStatus(
		array $data,
		?UserIdentity $savedTempUser,
		IContextSource $context
	): static {
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
		$value = $this->getValue();
		'@phan-var array{savedTempUser:?UserIdentity,context:IContextSource} $value';
		return $value['savedTempUser'];
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
		$value = $this->getValue();
		'@phan-var array{savedTempUser:?UserIdentity,context:IContextSource} $value';
		return $value['context'];
	}

}
