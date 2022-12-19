<?php

declare( strict_types = 1 );
namespace Wikibase\Client\DataBridge;

use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\FileModule;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Modules\MediaWikiConfigModule;

/**
 * Dynamically registering data bridge resource loader modules in extension.json
 * It should be deleted and moved to extension.json once the feature flag has been removed.
 *
 * @license GPL-2.0-or-later
 */
class DataBridgeResourceLoaderModules {
	public static function initModule() {
		$clientSettings = WikibaseClient::getSettings();
		return new FileModule(
			[
				'es6' => true,
				'scripts' => [
					'data-bridge.chunk-vendors.js',
					'data-bridge.init.js',
				],
				'targets' => $clientSettings->getSetting( 'dataBridgeEnabled' ) ?
					[ 'desktop', 'mobile' ] :
					[],
				'dependencies' => [
					'oojs-ui-windows',
					'mw.config.values.wbDataBridgeConfig',
				],
				'remoteExtPath' => 'Wikibase/client/data-bridge/dist',
			],
			__DIR__ . '/../../data-bridge/dist'
		);
	}

	public static function externalModifiersModule() {
		$clientSettings = WikibaseClient::getSettings();
		return new FileModule(
			[
				'styles' => [
					'edit-links.css',
					'box-layout.css',
				],
				'targets' => $clientSettings->getSetting( 'dataBridgeEnabled' ) ?
					[ 'desktop', 'mobile' ] :
					[],
				'remoteExtPath' => 'Wikibase/client/data-bridge/modules/externalModifiers',
			],
			__DIR__ . '/../../data-bridge/modules/externalModifiers'
		);
	}

	public static function configModule() {
		$clientSettings = WikibaseClient::getSettings();
		return new MediaWikiConfigModule(
			[
				'es6' => true,
				'getconfigvalueprovider' => function () use ( $clientSettings ) {
					return new DataBridgeConfigValueProvider(
						$clientSettings,
						MediaWikiServices::getInstance()->getMainConfig()->get( 'EditSubmitButtonLabelPublish' )
					);
				},
				'targets' => $clientSettings->getSetting( 'dataBridgeEnabled' ) ?
					[ 'desktop', 'mobile' ] :
					[],
			]
		);
	}

	public static function appModule() {
		$clientSettings = WikibaseClient::getSettings();
		return new FileModule(
			[
				'es6' => true,
				'scripts' => [
					'data-bridge.app.js',
				],
				'styles' => [
					'css/data-bridge.app.css',
				],
				'targets' => $clientSettings->getSetting( 'dataBridgeEnabled' ) ?
					[ 'desktop', 'mobile' ] :
					[],
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
			__DIR__ . '/../../data-bridge/dist'
		);
	}
}
