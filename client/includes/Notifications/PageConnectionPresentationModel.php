<?php

namespace Wikibase\Client\Notifications;

use EchoEvent;
use EchoEventPresentationModel;
use Title;

/**
 * Presentation model for Echo notifications
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 */
class PageConnectionPresentationModel extends EchoEventPresentationModel {

	public function callbackForBundleCount( EchoEvent $event ) {
		return $event->getTitle()->getPrefixedText();
	}

	public function getIconType() {
		return 'page-connection';
	}

	public function canRender() {
		return (bool)$this->event->getTitle()->exists();
	}

	public function getHeaderMessageKey() {
		if ( $this->getBundleCount( true, [ $this, 'callbackForBundleCount' ] ) > 1 ) {
			return "notification-bundle-header-{$this->type}";
		}
		return "notification-header-{$this->type}";
	}

	public function getHeaderMessage() {
		$msg = parent::getHeaderMessage();
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		$count = $this->getNotificationCountForOutput(
			false, // we need only other pages count
			[ $this, 'callbackForBundleCount' ]
		);
		if ( $count > 0 ) {
			$msg->numParams( $count );
		}
		return $msg;
	}

	public function getPrimaryLink() {
		$title = $this->event->getTitle();
		return [
			'url' => $title->getFullURL(),
			'label' => $title->getFullText()
		];
	}

	public function getSecondaryLinks() {
		$extra = $this->event->getExtra();
		$ret = [];

		if ( $this->getBundleCount( true, [ $this, 'callbackForBundleCount' ] ) === 1 ) {
			$ret[] = $this->getAgentLink();
			$ret[] = [
				'url' => $extra['url'],
				'label' => $this->msg( 'notification-link-text-view-item' )->text(),
				'description' => '',
				'icon' => 'changes',
				'prioritized' => true,
			];
		}

		$message = $this->msg( 'notification-page-connection-link' );
		if ( !$message->isDisabled() ) {
			$title = Title::newFromText( $message->plain() );
			if ( $title->exists() ) {
				$ret[] = [
					'url' => $title->getFullURL(),
					'label' => $this->msg( 'echo-learn-more' )->text(),
					'description' => '',
					'icon' => 'help',
					'prioritized' => false,
				];
			}
		}

		return $ret;
	}

}
