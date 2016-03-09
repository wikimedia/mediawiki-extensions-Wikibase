<?php

namespace Wikibase\Client\Notifications;

class PageConnectionPresentationModel extends \EchoEventPresentationModel {

	public function getIconType() {
		return 'placeholder'; // create page-connection
	}

	public function canRender() {
		return (bool)$this->event->getTitle()->exists();
	}

	public function getHeaderMessageKey() {
		// TODO: bundling
		return "notification-header-page-connection";
	}

	public function getHeaderMessage() {
		$msg = parent::getHeaderMessage();
		$msg->params( $this->event->getTitle()->getFullText() );
		return $msg;
	}

	public function getPrimaryLink() {
		$title = $this->event->getTitle();
		return array(
			'url' => $title->getFullURL(),
			'label' => $title->getFullText()
		);
	}

	public function getSecondaryLinks() {
		$extra = $this->event->getExtra();
		$ret = array();

		$ret[] = $this->getAgentLink();
		$ret[] = array(
			'url' => $extra['url'],
			'label' => $this->msg( 'notification-link-text-view-item' )->text(),
			'description' => '',
			'icon' => '', // missing
			'prioritized' => false,
		);

		$message = $this->msg( 'notification-page-connection-link' );
		if ( !$message->isDisabled() ) {
			$title = \Title::newFromText( $message->plain() );
			if ( $title->exists() ) {
				$ret[] = array(
					'url' => $title->getFullURL(),
					'label' => $this->msg( 'echo-learn-more' )->text(),
					'description' => '',
					'icon' => '', // missing
					'prioritized' => false,
				);
			}
		}

		return $ret;
	}
}