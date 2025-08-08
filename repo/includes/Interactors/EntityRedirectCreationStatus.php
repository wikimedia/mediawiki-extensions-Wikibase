<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Interactors;

use MediaWiki\Context\IContextSource;
use MediaWiki\User\UserIdentity;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Repo\TempUserStatus;
use Wikimedia\Assert\Assert;

/**
 * A Status representing the result of an {@link EntityRedirectCreationInteractor}.
 *
 * @inherits TempUserStatus<array{savedTempUser:?UserIdentity,context:IContextSource,entityRedirect:EntityRedirect}>
 * @license GPL-2.0-or-later
 */
class EntityRedirectCreationStatus extends TempUserStatus {

	public static function newRedirect(
		EntityRedirect $entityRedirect,
		?UserIdentity $savedTempUser,
		IContextSource $context
	): self {
		return self::newTempUserStatus( [
			'entityRedirect' => $entityRedirect,
		], $savedTempUser, $context );
	}

	/**
	 * The redirect that the edit resulted in.
	 * Only meaningful if the status is {@link self::isOK() OK}.
	 */
	public function getRedirect(): EntityRedirect {
		Assert::precondition( $this->isOK(), '$this->isOK()' );
		return $this->getValue()['entityRedirect']; // TODO reconcile method name and array member name?
	}

}
