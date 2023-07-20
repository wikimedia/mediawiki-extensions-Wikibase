<?php

namespace Wikibase\Client\Hooks;

use ApiMain;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use Parser;
use Wikibase\Client\Api\ApiFormatReference;
use Wikibase\Client\DataAccess\ReferenceFormatterFactory;
use Wikibase\DataModel\Deserializers\DeserializerFactory;

/**
 * Do special hook registrations. These are affected by ordering issues and/or
 * conditional on another extension being registered.
 *
 * Strictly speaking, this isn’t a hook handler
 * (it’s called through $wgExtensionFunctions, not $wgHooks),
 * but it behaves very similarly so it’s grouped with the real hook handlers.
 *
 * @see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:$wgExtensionFunctions
 * @license GPL-2.0-or-later
 */
class ExtensionLoadHandler {

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct(
		HookContainer $hookContainer
	) {
		$this->hookContainer = $hookContainer;
	}

	public static function factory(): self {
		return new self(
			MediaWikiServices::getInstance()->getHookContainer()
		);
	}

	public static function onExtensionLoad() {
		global $wgWBClientSettings, $wgAPIModules;

		$handler = self::factory();

		$handler->registerHooks();

		if ( $wgWBClientSettings === null ) {
			$wgWBClientSettings = [];
		}

		$apiFormatReferenceSpec = $handler->getApiFormatReferenceSpec( $wgWBClientSettings );
		if ( $apiFormatReferenceSpec !== null ) {
			$wgAPIModules['wbformatreference'] = $apiFormatReferenceSpec;
		}
	}

	/**
	 * Register the appropriate hooks in the HookContainer passed to the constructor.
	 */
	public function registerHooks(): void {
		// This is in onExtensionLoad to ensure we register our
		// ChangesListSpecialPageStructuredFilters after ORES's.
		//
		// However, ORES is not required.
		//
		// recent changes / watchlist hooks
		$this->hookContainer->register( 'ChangesListSpecialPageStructuredFilters',
			ChangesListSpecialPageHookHandler::class . '::onChangesListSpecialPageStructuredFilters' );
	}

	public function getApiFormatReferenceSpec( array $clientSettings ): ?array {
		// This API module is (for now) only enabled conditionally
		if ( !( $clientSettings['dataBridgeEnabled'] ?? false ) ) {
			return null;
		}

		return [
			'class' => ApiFormatReference::class,
			'services' => [
				'Parser',
				'WikibaseClient.BaseDataModelDeserializerFactory',
				'WikibaseClient.ReferenceFormatterFactory',
			],
			'factory' => function (
				ApiMain $apiMain,
				string $moduleName,
				Parser $parser,
				DeserializerFactory $deserializerFactory,
				ReferenceFormatterFactory $referenceFormatterFactory
			) {
				return new ApiFormatReference(
					$apiMain,
					$moduleName,
					$parser,
					$referenceFormatterFactory,
					$deserializerFactory->newReferenceDeserializer()
				);
			},
		];
	}

}
