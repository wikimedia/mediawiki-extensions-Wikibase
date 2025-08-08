<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Interactors;

use MediaWiki\Context\IContextSource;
use MediaWiki\User\UserIdentity;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\TempUserStatus;
use Wikimedia\Assert\Assert;

/**
 * A Status representing the result of an {@link ItemMergeInteractor}.
 *
 * @inherits TempUserStatus<array{savedTempUser:?UserIdentity,context:IContextSource,fromEntityRevision:EntityRevision,toEntityRevision:EntityRevision,redirected:bool}>
 * @license GPL-2.0-or-later
 */
class ItemMergeStatus extends TempUserStatus {

	public static function newMerge(
		EntityRevision $fromEntityRevision,
		EntityRevision $toEntityRevision,
		?UserIdentity $savedTempUser,
		IContextSource $context,
		?bool $redirected = null
	): self {
		$data = [
			'fromEntityRevision' => $fromEntityRevision,
			'toEntityRevision' => $toEntityRevision,
		];
		// $redirected is optional only for ItemMergeInteractor::attemptSaveMerge()
		if ( $redirected !== null ) {
			$data['redirected'] = $redirected;
		}
		return self::newTempUserStatus( $data, $savedTempUser, $context );
	}

	/**
	 * The modified source item, after it was merged but before it was potentially redirected.
	 * Only meaningful if the status is {@link self::isOK() OK}.
	 */
	public function getFromEntityRevision(): EntityRevision {
		Assert::precondition( $this->isOK(), '$this->isOK()' );
		return $this->getValue()['fromEntityRevision'];
	}

	/**
	 * The modified target item, after it was merged.
	 * Only meaningful if the status is {@link self::isOK() OK}.
	 */
	public function getToEntityRevision(): EntityRevision {
		Assert::precondition( $this->isOK(), '$this->isOK()' );
		return $this->getValue()['toEntityRevision'];
	}

	/**
	 * Whether the redirect was successful.
	 * Only meaningful if the status is {@link self::isOK() OK}.
	 */
	public function getRedirected(): bool {
		Assert::precondition( $this->isOK(), '$this->isOK()' );
		return $this->getValue()['redirected'];
	}

}
