<?php

namespace Wikibase\Client\Notifications;

use EchoEditFormatter;
use EchoNotificationController;

class PageConnectionFormatter extends EchoEditFormatter {

	/**
	 * @param EchoEvent $event
	 * @param string $param
	 * @param Message $message
	 * @param User $user
	 */
	protected function processParam( $event, $param, $message, $user ) {
		switch ( $param ) {
			case 'item':
				$extra = $event->getExtra();
				$message->params( $extra['entityId']->getSerialization() );
				break;
			case 'page-count':
				$count = EchoNotificationController::getCappedNotificationCount(
					$this->bundleData['other-page-count']
				);
				$message->numParams( $count );
				break;
			default:
				parent::processParam( $event, $param, $message, $user );
				break;
		}
	}

	/**
	 * @param EchoEvent $event
	 * @param Message $message
	 * @param string $type
	 */
	protected function generateBundleData( $event, $user, $type ) {
		$data = $this->getRawBundleData( $event, $user, $type );
		if ( !$data ) {
			return;
		}

		$title = $event->getTitle();
		if ( !$title || !$title->exists() ) {
			return;
		}

		$otherTitles = [];
		foreach ( $data as $bundledEvent ) {
			$title = $bundledEvent->getTitle();
			if ( $title->exists() ) {
				$otherTitles[] = $title->getPrefixedText();
			}
		}

		$count = count( array_unique( $otherTitles ) );
		if ( $count > 0 ) {
			$this->bundleData['use-bundle'] = true;
			$this->bundleData['other-page-count'] = $count;
		}
	}

}
