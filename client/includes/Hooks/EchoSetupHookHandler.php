<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Extension\Notifications\AttributeManager;
use MediaWiki\Extension\Notifications\Hooks\BeforeCreateEchoEventHook;
use MediaWiki\Extension\Notifications\UserLocator;
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
class EchoSetupHookHandler implements BeforeCreateEchoEventHook {

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
			AttributeManager::ATTR_LOCATORS => [
				[ [ UserLocator::class, 'locateArticleCreator' ] ],
			],
			'category' => 'wikibase-action',
			'group' => 'neutral',
			'section' => 'message',
			'presentation-model' => PageConnectionPresentationModel::class,
			'bundle' => [ 'web' => true, 'email' => false ],
			'apply-page-link-mute' => true,
		];

		$icons[EchoNotificationsHandlers::NOTIFICATION_TYPE] = $this->echoIcon ?: [
			'path' => 'Wikibase/client/resources/images/echoIcon.svg',
		];
	}

}
