<?php

namespace Wikibase\Client\Notifications;

use MediaWiki\Notification\Middleware\FilterMiddleware;
use MediaWiki\Notification\NotificationEnvelope;
use MediaWiki\Watchlist\RecentChangeNotification;
use Wikibase\Client\RecentChanges\RecentChangeFactory;

/**
 * Middleware to filter out watchlist notifications for Wikibase changes
 *
 * @license GPL-2.0-or-later
 * @author Piotr Miazga
 */
class WikibaseWatchlistNotificationMiddleware extends FilterMiddleware {

	protected function filter( NotificationEnvelope $envelope ): bool {
		$notification = $envelope->getNotification();

		if ( $notification instanceof RecentChangeNotification &&
			$notification->getRecentChange()->getAttribute( 'rc_source' ) === RecentChangeFactory::SRC_WIKIBASE
		) {
			return self::REMOVE;
		}

		return self::KEEP;
	}
}
