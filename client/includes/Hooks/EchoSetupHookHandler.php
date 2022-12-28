<?php

namespace Wikibase\Client\Hooks;

use EchoAttributeManager;
use EchoUserLocator;
use Wikibase\Client\Notifications\PageConnectionPresentationModel;
use Wikibase\Lib\SettingsArray;

/**
 * Handlers for hooks (e.g. BeforeCreateEchoEvent) called when Echo extension
 * is initialized, so on every page load.
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EchoSetupHookHandler
	/* TODO this should implement an interface from Echo */ {

	/**
	 * @var bool
	 */
	private $sendEchoNotification;

	/**
	 * @var array|false
	 */
	private $echoIcon;

	/**
	 * @param bool $sendEchoNotification
	 * @param array|false $echoIcon
	 */
	public function __construct( $sendEchoNotification, $echoIcon ) {
		$this->sendEchoNotification = $sendEchoNotification;
		$this->echoIcon = $echoIcon;
	}

	public static function factory(
		SettingsArray $clientSettings
	): self {
		return new self(
			$clientSettings->getSetting( 'sendEchoNotification' ),
			$clientSettings->getSetting( 'echoIcon' )
		);
	}

	/**
	 * Handler for BeforeCreateEchoEvent hook
	 * @see https://www.mediawiki.org/wiki/Extension:Echo/BeforeCreateEchoEvent
	 * @see https://www.mediawiki.org/wiki/Notifications/Developer_guide
	 *
	 * @param array[] &$notifications
	 * @param array[] &$notificationCategories
	 * @param array[] &$icons
	 */
	public function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$icons
	) {
		if ( $this->sendEchoNotification !== true ) {
			return;
		}

		$notificationCategories['wikibase-action'] = [
			'priority' => 5,
			'tooltip' => 'echo-pref-tooltip-wikibase-action',
		];

		$notifications[EchoNotificationsHandlers::NOTIFICATION_TYPE] = [
			EchoAttributeManager::ATTR_LOCATORS => [
				[ [ EchoUserLocator::class, 'locateArticleCreator' ] ],
			],
			'category' => 'wikibase-action',
			'group' => 'neutral',
			'section' => 'message',
			'presentation-model' => PageConnectionPresentationModel::class,
			'bundle' => [ 'web' => true, 'email' => false ],
		];

		$icons[EchoNotificationsHandlers::NOTIFICATION_TYPE] = $this->echoIcon ?: [
			'path' => 'Wikibase/client/resources/images/echoIcon.svg',
		];
	}

}
