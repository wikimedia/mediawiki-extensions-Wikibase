<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Changes;

use CentralIdLookup;
use MediaWiki\User\UserIdentity;
use MWException;
use User;

/**
 * Assumes a central system with only two repositories, a repo wiki and a client.
 *
 * All IDs are multiples of the following scheme:
 *
 * central = -1, repo = 1, client = 2
 *
 * Takes a constructor parameter to specify which this is.
 *
 * We don't need the methods that operate by username, so we don't implement them and
 * instead reimplement the methods used by Wikibase.
 *
 * @license GPL-2.0-or-later
 * @author Matthew Flaschen < mflaschen@wikimedia.org >
 */
class MockRepoClientCentralIdLookup extends CentralIdLookup {
	/**
	 * @var int Factor to multiply by to go from this wiki to the central ID
	 */
	private $toCentralFactor;

	/**
	 * @param bool $isRepo True if this is the repo, false otherwise
	 */
	public function __construct( bool $isRepo ) {
		if ( $isRepo ) {
			$this->toCentralFactor = -1;
		} else {
			$this->toCentralFactor = -0.5;
		}
	}

	public function isAttached( UserIdentity $user, $wikiId = null ): bool {
		return true;
	}

	public function lookupCentralIds(
		array $idToName, $audience = self::AUDIENCE_PUBLIC, $flags = self::READ_NORMAL
	): array {
		throw new MWException( 'Not implemented' );
	}

	public function lookupUserNames(
		array $nameToId, $audience = self::AUDIENCE_PUBLIC, $flags = self::READ_NORMAL
	): array {
		throw new MWException( 'Not implemented' );
	}

	public function localUserFromCentralId(
		$id, $audience = self::AUDIENCE_PUBLIC, $flags = self::READ_NORMAL
	): ?UserIdentity {
		if ( $id >= 0 ) {
			// Invalid central ID
			return null;
		}

		$localUserId = $id / $this->toCentralFactor;

		return User::newFromId( $localUserId );
	}

	public function centralIdFromLocalUser(
		UserIdentity $user, $audience = self::AUDIENCE_PUBLIC, $flags = self::READ_NORMAL
	): int {
		$localUserId = $user->getId();

		return $localUserId * $this->toCentralFactor;
	}

}
