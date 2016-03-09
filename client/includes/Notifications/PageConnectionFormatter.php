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
				if ( $this->getBundleData()['raw-data-count'] > 1 ) {
					$message->params( 2 );
				} else {
					$message->params( 1 );
				}
				break;
			default:
				parent::processParam( $event, $param, $message, $user );
				break;
		}
	}
}