<?php

namespace Wikibase\Client\Notifications;

class PageConnectionFormatter extends \EchoEditFormatter {

	protected function processParam( $event, $param, $message, $user ) {
		switch ( $param ) {
			case 'item':
				$extra = $event->getExtra();
				$message->params( $extra['entityId']->getSerialization() );
				break;
			case 'page-count':
				// for email subject
				$this->getRawBundleData( $event, $user, 'dummy' );
				$count = $this->getBundleData()['raw-data-count'];
				// was decremented but the message tells about all the events
				$cappedCount = \EchoNotificationController::getCappedNotificationCount( $count + 1 );
				$message->numParams( $cappedCount );
				break;
			default:
				parent::processParam( $event, $param, $message, $user );
				break;
		}
	}
}
