<?php

namespace Wikibase\Repo\Notifications;

use Revision;
use User;
use Wikibase\EntityContent;

/**
 * @license GPL 2+
 * @author Thiemo Mättig
 */
class NullChangeNotifier implements ChangeNotifier {

	/**
	 * @see ChangeNotifier::notifyOnPageDeleted
	 */
	public function notifyOnPageDeleted( EntityContent $content, User $user, $timestamp ) {
		return null;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageUndeleted
	 */
	public function notifyOnPageUndeleted( EntityContent $content, User $user, $revisionId, $timestamp ) {
		return null;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageCreated
	 */
	public function notifyOnPageCreated( Revision $revision ) {
		return null;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageModified
	 */
	public function notifyOnPageModified( Revision $current, Revision $parent ) {
		return null;
	}

}
