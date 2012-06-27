<?php

namespace STTLanguage;

/**
 * Initialization file for the 'Stick to That Language' extension.
 *
 * Documentation:  https://www.mediawiki.org/wiki/Extension:Stick_to_That_Language
 * Support:        https://www.mediawiki.org/wiki/Extension_talk:Stick_to_That_Language
 * Source code:    https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataRepo.git
 *
 * TODO:
 * - make language stick somehow
 *
 * @file StickToThatLanguage.php
 * @ingroup STTLanguage
 *
 * @version: 0.1 alpha
 * @licence GNU GPL v2+
 * @author: Daniel Werner < daniel.werner@wikimedia.de >
 */

if( ! defined( 'MEDIAWIKI' ) ) { die(); }

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'Stick to That Language',
	'descriptionmsg' => 'sticktothatlanguage-desc',
	'version'        => Ext::VERSION,
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Stick_to_That_Language',
	'author'         => array( '[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]' ),
);

// i18n
$wgExtensionMessagesFiles['StickToThatLanguage'] = Ext::getDir() . '/StickToThatLanguage.i18n.php';

// Autoloading
$wgAutoloadClasses['STTLanguage\Hooks']   = Ext::getDir() . '/StickToThatLanguage.hooks.php';

// hooks registration:
$wgHooks['GetPreferences'][]                   = 'STTLanguage\Hooks::onGetPreferences';
$wgHooks['UserGetDefaultOptions'][]            = 'STTLanguage\Hooks::onUserGetDefaultOptions';
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'STTLanguage\Hooks::onSkinTemplateOutputPageBeforeExec';
$wgHooks['UnitTestsList'][]                    = 'STTLanguage\Hooks::registerUnitTests';

// Include settings:
require_once Ext::getDir() . '/StickToThatLanguage.settings.php';


/**
 * 'Stick to That Language' extension class with basic extension information and functions which can be used
 * by other extensions.
 *
 * @since 0.1
 */
class Ext {
	/**
	 * Version of the extension.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	const VERSION = '0.1 alpha';

	/**
	 * Returns the extensions base installation directory.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getDir() {
		static $dir = null;

		if( $dir === null ) {
			$dir = dirname( __FILE__ );
		}
		return $dir;
	}

	/**
	 * Returns the list of languages the user has set as preferred languages in the preferences.
	 * This also includes the users main language always.
	 *
	 * @since 0.1
	 *
	 * @param User $user
	 * @return array with language codes as values
	 */
	public static function getUserLanguageCodes( $user ) {
		$languageCodes = array();

		// check for all languages whether they are selected as users preferred language:
		foreach( \Language::fetchLanguageNames() as $code => $name ) {
			if( $user->getOption( "sttl-languages-$code" ) ) {
				$languageCodes[] = $code;
			}
		}
		// make sure users overall language is represented within:
		$userLang = $user->getOption( 'language' );
		if( !in_array( $userLang, $languageCodes ) ) {
			$languageCodes[] = $userLang;
		}

		return $languageCodes;
	}
}
