<?php

namespace Wikibase\Client\Notifications;

class PageConnectionFormatter extends \EchoEditFormatter {
	protected function processParam( $event, $param, $message, $user ) {
		switch ( $param ) {
			case 'item':
				$extra = $event->getExtra();
				$message->params( $extra['entityId']->getSerialization() );
				break;

			default:
				parent::processParam( $event, $param, $message, $user );
				break;
		}
	}
}