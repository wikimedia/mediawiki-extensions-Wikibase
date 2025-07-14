<?php

namespace Wikibase\Client\Notifications;

use MediaWiki\Notification\Middleware\FilterMiddleware;
use MediaWiki\Notification\Middleware\FilterMiddlewareAction;
use MediaWiki\Notification\NotificationEnvelope;
use MediaWiki\RecentChanges\RecentChangeNotification;
use Wikibase\Client\RecentChanges\RecentChangeFactory;

/**
 * Middleware to filter out watchlist notifications for Wikibase changes
 *
 * @license GPL-2.0-or-later
 * @author Piotr Miazga
 */
class WikibaseWatchlistNotificationMiddleware extends FilterMiddleware {

	protected function filter( NotificationEnvelope $envelope ): FilterMiddlewareAction {
		$notification = $envelope->getNotification();

		if ( $notification instanceof RecentChangeNotification &&
			$notification->getRecentChange()->getAttribute( 'rc_source' ) === RecentChangeFactory::SRC_WIKIBASE
		) {
			return FilterMiddlewareAction::REMOVE;
		}

		return FilterMiddlewareAction::KEEP;
	}
}
