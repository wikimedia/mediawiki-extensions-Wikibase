<?php

namespace Wikibase\Client\Notifications;

use EchoEvent;
use EchoEventPresentationModel;
use Title;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;

/**
 * Presentation model for Echo notifications
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 */
class PageConnectionPresentationModel extends EchoEventPresentationModel {

	/**
	 * @param EchoEvent
	 * @return string
	 */
	public function callbackForBundleCount( EchoEvent $event ) {
		return $event->getTitle()->getPrefixedText();
	}

	/**
	 * @see EchoEventPresentationModel::getIconType()
	 */
	public function getIconType() {
		return EchoNotificationsHandlers::NOTIFICATION_TYPE;
	}

	/**
	 * @see EchoEventPresentationModel::canRender()
	 */
	public function canRender() {
		return $this->event->getTitle()->exists();
	}

	/**
	 * @see EchoEventPresentationModel::getHeaderMessage()
	 */
	public function getHeaderMessage() {
		$count = $this->getNotificationCountForOutput(
			false, // we need only other pages count
			[ $this, 'callbackForBundleCount' ]
		);

		$truncated = $this->getTruncatedTitleText( $this->event->getTitle(), true );

		if ( $count > 0 ) {
			$msg = $this->getMessageWithAgent( "notification-bundle-header-{$this->type}" )
				->params( $truncated )
				->numParams( $count );
		} else {
			$msg = $this->getMessageWithAgent( "notification-header-{$this->type}" )
				->params( $truncated );
		}

		return $msg;
	}

	/**
	 * @see EchoEventPresentationModel::getSubjectMessageKey()
	 */
	protected function getSubjectMessageKey() {
		return "notification-subject-{$this->type}";
	}

	/**
	 * @see EchoEventPresentationModel::getSubjectMessage()
	 */
	public function getSubjectMessage() {
		return parent::getSubjectMessage()->params( $this->getViewingUserForGender() );
	}

	/**
	 * @see EchoEventPresentationModel::getPrimaryLink()
	 */
	public function getPrimaryLink() {
		$title = $this->event->getTitle();
		return [
			'url' => $title->getFullURL(),
			'label' => $title->getFullText()
		];
	}

	/**
	 * @see EchoEventPresentationModel::getSecondaryLinks()
	 */
	public function getSecondaryLinks() {
		$ret = [];

		if ( $this->getBundleCount( true, [ $this, 'callbackForBundleCount' ] ) === 1 ) {
			$ret[] = $this->getAgentLink();
			$ret[] = [
				'url' => $this->event->getExtraParam( 'url' ),
				'label' => $this->msg(
					'notification-link-text-view-item',
					$this->getViewingUserForGender()
					)->text(),
				'description' => '',
				'icon' => 'changes',
				'prioritized' => true,
			];
		}

		$message = $this->msg( 'notification-page-connection-link',
			$this->event->getExtraParam( 'repoSiteName' ) );
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
