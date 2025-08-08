<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\EditEntity;

use ArrayAccess;
use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use StatusValue;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\TempUserStatus;
use Wikimedia\Assert\Assert;

/**
 * A Status representing the result of an {@link EditEntity} edit.
 *
 * @inherits TempUserStatus<array{savedTempUser:?UserIdentity,context:IContextSource,revision:EntityRevision}>
 * @license GPL-2.0-or-later
 */
class EditEntityStatus extends TempUserStatus {

	public static function newEdit(
		EntityRevision $revision,
		?UserIdentity $savedTempUser,
		IContextSource $context
	): self {
		return self::newTempUserStatus( [
			'revision' => $revision,
		], $savedTempUser, $context );
	}

	/**
	 * @param StatusValue|Status $sv
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

	/**
	 * Set the "OK" flag to false and the value to the given error flags.
	 */
	public function setErrorFlags( int $errorFlags ): void {
		$this->setResult( false, [ 'errorFlags' => $errorFlags ] );
	}

	/**
	 * The revision that the edit resulted in.
	 * Only meaningful if the status is {@link self::isOK() OK}.
	 */
	public function getRevision(): EntityRevision {
		Assert::precondition( $this->isOK(), '$this->isOK()' );
		return $this->getValue()['revision'];
	}

	/**
	 * Bitfield indicating errors;
	 * see the XXX_ERROR constants in {@link EditEntity}.
	 * Only meaningful if the status is *not* {@link self::isOK() OK},
	 * and not guaranteed to be present even then.
	 */
	public function getErrorFlags(): ?int {
		Assert::precondition( !$this->isOK(), '!$this->isOK()' );
		$value = $this->getValue();
		if ( is_array( $value ) || $value instanceof ArrayAccess ) {
			// @phan-suppress-next-line PhanTypeMismatchReturn
			return $value['errorFlags'] ?? null;
		} else {
			return null;
		}
	}

}
