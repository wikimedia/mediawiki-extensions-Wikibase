<?php

namespace Wikibase\Repo\Notifications;

use Revision;
use User;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\Lib\Changes\EntityChangeFactory;

/**
 * Class for generating and submitting change notifications in different situations.
 * This is a helper intended for use in hook handler functions.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DatabaseChangeNotifier implements ChangeNotifier {

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	public function __construct( EntityChangeFactory $changeFactory ) {
		$this->changeFactory = $changeFactory;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageDeleted
	 */
	public function notifyOnPageDeleted( EntityContent $content, User $user, $timestamp ) {
		return $this->notify( $this->changeFactory->getOnPageDeletedChange( $content, $user, $timestamp ) );
	}

	/**
	 * @see ChangeNotifier::notifyOnPageUndeleted
	 */
	public function notifyOnPageUndeleted( Revision $revision ) {
		return $this->notify( $this->changeFactory->getOnPageUndeletedChange( $revision ) );
	}

	/**
	 * @see ChangeNotifier::notifyOnPageCreated
	 */
	public function notifyOnPageCreated( Revision $revision ) {
		return $this->notify( $this->changeFactory->getOnPageCreatedChange( $revision ) );
	}

	/**
	 * @see ChangeNotifier::notifyOnPageModified
	 */
	public function notifyOnPageModified( Revision $current, Revision $parent ) {
		return $this->notify( $this->changeFactory->getOnPageModifiedChange( $current, $parent ) );
	}

	/**
	 * @param EntityChange|null $change
	 *
	 * @throws ChangeTransmitterException
	 * @return EntityChange|null
	 */
	private function notify( EntityChange $change = null ) {
		if ( $change !== null && !$change->save() ) {
			throw new ChangeTransmitterException( 'Failed to record change to the database.' );
		}

		return $change;
	}

}
