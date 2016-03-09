<?php

namespace Wikibase\Client\Notifications;

class PageConnectionPresentationModel extends \EchoEventPresentationModel {

	public function getIconType() {
		return 'page-connection';
	}

	public function canRender() {
		return (bool)$this->event->getTitle()->exists();
	}

	public function getHeaderMessageKey() {
		if ( $this->isBundled() ) {
			return "notification-bundle-header-page-connection";
		}
		return "notification-header-page-connection";
	}

	public function getHeaderMessage() {
		$msg = parent::getHeaderMessage();
		$msg->params( $this->event->getTitle()->getFullText() );
		if ( $this->isBundled() ) {
			$msg->numParams( $this->getNotificationCountForOutput( false ) );
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

		if ( !$this->isBundled() ) {
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
			$title = \Title::newFromText( $message->plain() );
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
