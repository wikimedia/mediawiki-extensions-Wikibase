<?php

declare( strict_types = 1 );

namespace Wikibase\Client\DataBridge;

use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Modules\MediaWikiConfigModule;

/**
 * Dynamically registering data bridge resource loader modules in extension.json
 * It should be deleted and moved to extension.json once the feature flag has been removed.
 *
 * @license GPL-2.0-or-later
 */
class DataBridgeResourceLoaderModules implements ResourceLoaderRegisterModulesHook {

	public function onResourceLoaderRegisterModules( ResourceLoader $rl ): void {
		$clientSettings = WikibaseClient::getSettings();
		if ( !$clientSettings->getSetting( 'dataBridgeEnabled' ) ) {
			return;
		}

		$rl->register( [
			'wikibase.client.data-bridge.init' => [
				'scripts' => [
					'data-bridge.chunk-vendors.js',
					'data-bridge.init.js',
				],
				'dependencies' => [
					'oojs-ui-windows',
					'mw.config.values.wbDataBridgeConfig',
				],
				'localBasePath' => __DIR__ . '/../../data-bridge/dist',
				'remoteExtPath' => 'Wikibase/client/data-bridge/dist',
			],
			'wikibase.client.data-bridge.externalModifiers' => [
				'styles' => [
					'edit-links.css',
					'box-layout.css',
				],
				'localBasePath' => __DIR__ . '/../../data-bridge/modules/externalModifiers',
				'remoteExtPath' => 'Wikibase/client/data-bridge/modules/externalModifiers',
			],
			'mw.config.values.wbDataBridgeConfig' => [
				'class' => MediaWikiConfigModule::class,
				'getconfigvalueprovider' => function () {
					$clientSettings = WikibaseClient::getSettings();
					return new DataBridgeConfigValueProvider(
						$clientSettings,
						MediaWikiServices::getInstance()->getMainConfig()->get( MainConfigNames::EditSubmitButtonLabelPublish )
					);
				},
			],
			'wikibase.client.data-bridge.app' => [
				'scripts' => [
					'data-bridge.app.js',
				],
				'styles' => [
					'css/data-bridge.app.css',
				],
				'localBasePath' => __DIR__ . '/../../data-bridge/dist',
				'remoteExtPath' => 'Wikibase/client/data-bridge/dist',
				'dependencies' => [
					'vue',
					'vuex',
					'mediawiki.jqueryMsg',
				],
				'messages' => [
					'wikibase-client-data-bridge-dialog-title',
					'wikibase-client-data-bridge-permissions-error',
					'wikibase-client-data-bridge-permissions-error-info',
					'wikibase-client-data-bridge-protected-on-repo-head',
					'wikibase-client-data-bridge-protected-on-repo-body',
					'wikibase-client-data-bridge-semiprotected-on-repo-head',
					'wikibase-client-data-bridge-semiprotected-on-repo-body',
					'wikibase-client-data-bridge-cascadeprotected-on-repo-head',
					'wikibase-client-data-bridge-cascadeprotected-on-repo-body',
					'wikibase-client-data-bridge-blocked-on-repo-head',
					'wikibase-client-data-bridge-blocked-on-repo-body',
					'wikibase-client-data-bridge-cascadeprotected-on-client-head',
					'wikibase-client-data-bridge-cascadeprotected-on-client-body',
					'wikibase-client-data-bridge-blocked-on-client-head',
					'wikibase-client-data-bridge-blocked-on-client-body',
					'wikibase-client-data-bridge-permissions-error-unknown-head',
					'wikibase-client-data-bridge-permissions-error-unknown-body',
					'wikibase-client-data-bridge-edit-decision-heading',
					'wikibase-client-data-bridge-edit-decision-replace-label',
					'wikibase-client-data-bridge-edit-decision-replace-description',
					'wikibase-client-data-bridge-edit-decision-update-label',
					'wikibase-client-data-bridge-edit-decision-update-description',
					'wikibase-client-data-bridge-references-heading',
					'wikibase-client-data-bridge-anonymous-edit-warning-heading',
					'wikibase-client-data-bridge-anonymous-edit-warning-message',
					'wikibase-client-data-bridge-anonymous-edit-warning-tempuser-message',
					'wikibase-client-data-bridge-anonymous-edit-warning-proceed',
					'wikibase-client-data-bridge-anonymous-edit-warning-login',
					'wikibase-client-data-bridge-license-heading',
					'wikibase-client-data-bridge-license-body',
					'wikibase-client-data-bridge-bailout-heading',
					'wikibase-client-data-bridge-bailout-suggestion-go-to-repo',
					'wikibase-client-data-bridge-bailout-suggestion-go-to-repo-button',
					'wikibase-client-data-bridge-bailout-suggestion-edit-article',
					'wikibase-client-data-bridge-unsupported-datatype-error-head',
					'wikibase-client-data-bridge-unsupported-datatype-error-body',
					'wikibase-client-data-bridge-deprecated-statement-error-head',
					'wikibase-client-data-bridge-deprecated-statement-error-body',
					'wikibase-client-data-bridge-ambiguous-statement-error-head',
					'wikibase-client-data-bridge-ambiguous-statement-error-body',
					'wikibase-client-data-bridge-somevalue-error-head',
					'wikibase-client-data-bridge-somevalue-error-body',
					'wikibase-client-data-bridge-saving-error-heading',
					'wikibase-client-data-bridge-saving-error-message',
					'wikibase-client-data-bridge-edit-conflict-error-heading',
					'wikibase-client-data-bridge-edit-conflict-error-message',
					'wikibase-client-data-bridge-novalue-error-head',
					'wikibase-client-data-bridge-novalue-error-body',
					'wikibase-client-data-bridge-unknown-error-heading',
					'wikibase-client-data-bridge-unknown-error-message',
					'wikibase-client-data-bridge-error-report',
					'wikibase-client-data-bridge-error-reload-bridge',
					'wikibase-client-data-bridge-error-reload-page',
					'wikibase-client-data-bridge-error-go-back',
					'wikibase-client-data-bridge-error-retry-save',
					'wikibase-client-data-bridge-reference-note',
					'wikibase-client-data-bridge-thank-you-head',
					'wikibase-client-data-bridge-thank-you-edit-reference-on-repo-body',
					'wikibase-client-data-bridge-thank-you-edit-reference-on-repo-button',
					"wikibase-client-data-bridge-saving-error-assertuser-heading",
					"wikibase-client-data-bridge-saving-error-assertuser-message",
					"wikibase-client-data-bridge-saving-error-assertuser-publish",
					"wikibase-client-data-bridge-saving-error-assertuser-save",
					"wikibase-client-data-bridge-saving-error-assertuser-login",
					"wikibase-client-data-bridge-saving-error-assertuser-editing",
					'savechanges',
					'publishchanges',
					'cancel',
					'grouppage-sysop',
					'emailuser',
				],
			],
		] );
	}
}
