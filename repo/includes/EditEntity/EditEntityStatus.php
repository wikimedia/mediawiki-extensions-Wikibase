<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\EditEntity;

use ArrayAccess;
use MediaWiki\Context\IContextSource;
use MediaWiki\User\UserIdentity;
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
			return $value['errorFlags'] ?? null;
		} else {
			return null;
		}
	}

}
