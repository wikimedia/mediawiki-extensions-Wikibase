<?php

namespace STTLanguage;

/**
 * File defining the hook handlers for the 'Stick to That Language' extension.
 *
 * @since 0.1
 *
 * @file StickToThatLanguage.hooks.php
 * @ingroup STTLanguage
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
final class Hooks {
	/**
	 * Registers PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.1
	 *
	 * @param array &$files
	 * @return bool
	 */
	public static function registerUnitTests( array &$files ) {
		$files[] = Ext::getDir() . '/tests/phpunit/ExtTest.php'; // STTLanguage\Ext (extension class)
		return true;
	}

	/**
	 * Adds the user preference for choosing other languages the user can speak.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @since 0.1
	 *
	 * @param \User $user
	 * @param array &$preferences
	 * @return bool
	 */
	public static function onGetPreferences( \User $user, array &$preferences ) {
		$preferences['sttl-languages'] = array(
			'type' => 'multiselect',
			'usecheckboxes' => false,
			'label-message' => 'sttl-setting-languages',
			'options' => $preferences['language']['options'], // all languages available in 'language' selector
			'section' => 'personal/i18n',
			'prefix' => 'sttl-languages-',
		);

		return true;
	}

	/**
	 * Called after fetching the core default user options.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGetDefaultOptions
	 *
	 * @param array &$defaultOptions
	 * @return bool
	 */
	public static function onUserGetDefaultOptions( array &$defaultOptions ) {
		// pre-select default language in the list of fallback languages
		$defaultLang = $defaultOptions['language'];
		$defaultOptions[ 'sttl-languages-' . $defaultLang ] = 1;

		return true;
	}

	/**
	 * Used to build the global language selector if activated
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateOutputPageBeforeExec
	 *
	 * @since 0.1
	 *
	 * @param \SkinTemplate $sk
	 * @param \QuickTemplate $tpl
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( \SkinTemplate &$sk, \QuickTemplate &$tpl ) {
		global $egSTTLanguageDisplaySelector, $egSTTLanguageTopLanguages;
		if( ! $egSTTLanguageDisplaySelector ) {
			return true; // option for disabling the selector is active
		}

		// NOTE: usually we would add the module styles for the selector here, but apparently at this point
		//       it is too late to add any styles to the OutputPage.

		// Title of our item:
		$title = $sk->getOutput()->getTitle();
		$user = $sk->getUser();

		$langUrls = array();
		$topLangUrls = array();

		$topLanguages = $user->isLoggedIn()
			? Ext::getUserLanguageCodes( $user ) // display users preferred languages on top
			: $egSTTLanguageTopLanguages;

		foreach( \Language::fetchLanguageNames() as $code => $name ) {
			if( $code === $sk->getLanguage()->getCode() ) {
				continue; // don't add language the page is displayed in
			}

			// set 'uselang' and in case this was a GET request also all other parameters when switching language
			$urlParams = array( 'uselang' => $code );
			if( ! $sk->getRequest()->wasPosted() ) {
				$urlParams += $sk->getRequest()->getValues();
				unset( $urlParams['title'] ); // don't want the 'title' twice
			}

			// build information for the skin to generate links for all languages:
			$url = array(
				'href' => $title->getFullURL( $urlParams ),
				'text' => $name,
				'title' => $title->getText(),
				'class' => "sttl-lang-$code", // site-links use 'interwiki-' which seems inappropriate in this case
				'lang' => $code,
				'hreflang' => $code,
			);

			$topIndex =  array_search( $code, $topLanguages );
			if( $topIndex !== false ) {
				// language is considered a 'top' language
				$url['class'] .= ' sttl-toplang';
				$topLangUrls[ $topIndex ] = $url;
			} else {
				$langUrls[] = $url;
			}
		}

		if( count( $topLangUrls ) ) { // can be empty when the only language in here is the one displayed!
			// make sure top languages are still in defined order and there are no gaps:
			ksort( $topLangUrls );
			$topLangUrls = array_values( $topLangUrls );
			// add another css class for the last preferred, after that, other languages start:
			$topLangUrls[ count( $topLangUrls ) - 1 ]['class'] .= ' sttl-lasttoplang';
		}

		// put preferred languages on top and add others:
		$language_urls = array_merge( $topLangUrls, $langUrls );

		// define these languages as languages for the sitebar within the skin:
		$tpl->setRef( 'language_urls', $language_urls );

		return true;
	}

	/**
	 * Used to include the language selectors required resource loader module.
	 *
	 * NOTE: can't do this in onSkinTemplateOutputPageBeforeExec. Seems like at that point of time it is not
	 *       possible to add them.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @since 0.1
	 *
	 * @param \OutputPage $out
	 * @param \Skin $skin
	 * @return bool
	 */
	public static function onBeforePageDisplay( \OutputPage &$out, \Skin &$skin ) {
		// add styles for the language selector:
		global $egSTTLanguageDisplaySelector;

		if( $egSTTLanguageDisplaySelector ) {
			// add styles for language selector (has to be done separately for non-JS version):
			$out->addModuleStyles( 'sticktothatlanguage' );
			$out->addModules( 'sticktothatlanguage' );
		}

		return true;
	}

	/**
	 * Makes the language sticky (mostly for links of the page content)
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinkBegin
	 *
	 * @param $skin
	 * @param $target
	 * @param $text
	 * @param $customAttribs
	 * @param $query
	 * @param $options
	 * @param $ret
	 * @return bool true
	 */
	public static function onLinkBegin( $skin, $target, &$text, &$customAttribs, &$query, &$options, &$ret ) {
		global $wgLang, $wgParser;

		if( is_string( $query ) ) {
			// this check is not yet in MW core (pending on review in 1.20). This can actually be a string
			$query = wfCgiToArray( $query );
		}

		/*
		 * The following will trigger a cache fragmentation. This means, the parsed output is only put into the cache
		 * cache for the current user language. This is necessary because otherwise the 'uselang' parameter would be
		 * wrong when accessing the page in another language as the one active during parsing for the cached version.
		 */
		self::causeCacheFragmentation();

		// NOTE: we can't just add 'uselang' in case it is different from the sites global language (e.g. 'en')
		//       because a users language could still be different than that language.

		if( array_key_exists( 'uselang', $query ) ) { // only add it if there is no uselang set to that link already!
			// this will add the 'uselang' parameter to each link generated with Linker::link()
			$query[ 'uselang' ] = $wgLang->getCode();
		}
		return true;
	}

	/**
	 * Makes the language stick (mostly for links other than the page content)
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetLocalURL::Internal
	 *
	 * @since 0.1
	 *
	 * @param \Title $title
	 * @param string $url
	 * @return bool true
	 */
	public static function onGetLocalUrlInternally( \Title $title, &$url ) {
		global $wgLang;

		/*
		 * We have to do this here as well, since content parsing could still come here instead of the onLinkBegin.
		 * This is for example happening when having an image with link.
		 */
		self::causeCacheFragmentation();

		// don't add uselang if there is a uselang set to that url already!
		if( !preg_match( '/[&\?]uselang=/i', $url ) ) {
			// this will add the 'uselang' parameter to each link returned by Title::getLocalURL()
			$url = wfAppendQuery( $url, 'uselang=' . $wgLang->getCode() );
		}

		return true;
	}
	/**
	 * Helper to cause some cache fragmentation (separate cached version of the article in all languages)
	 *
	 * @since 0.1
	 */
	private static function causeCacheFragmentation() {
		global $wgParser;
		if( $wgParser->getOptions() !== null ) { // if not parsing anything right now, this is set to null
			/*
			This will trigger cache fragmentation. The parser options will set some flag internally
			for generating a language specific parser cache key. This is basically the same what the
			'int' parser function does.
			*/
			$wgParser->getOptions()->getUserLangObj();
		}
	}

	/**
	 * Used to make the language sticky for all forms. This is done by actually grabbing the php output which is about
	 * to be sent to the user and running a regular expression over it to get the 'uselang' parameter into the post
	 * request of the form.
	 *
	 * @Todo: this is the top of the hackiness of this extension. It's double evil since we are running a regular
	 *        expression on the overall output, and because we are grabbing the output buffer directly and setting its
	 *        value with the regex-modified version of the output. This should be done differently, probably a cookie
	 *        solution would be the best after all.
	 *
	 * @since 0.1
	 *
	 * @param \OutputPage $out
	 * @return bool true
	 */
	public static function onAfterFinalPageOutput( \OutputPage $out ) {
		global $wgLang;
		$startTime = microtime();

		// removes everything from the output buffer and returns it, so we can actually modify it:
		$output = ob_get_clean();

		// regex which picks <form> if it is valid HTML (allows self-closing tags):
		/*
		$recursiveDomRegex = '(?P<innerDOM> <(\w+)(?:\s+[^>]*|)>(?: (?> (?!<\w+(?:\s+[^>]*|)[^\/]> | <\/\w+> ).)* | (?&innerDOM) )*?<\/ \4 > )*';
		$regex = '/(<form(?:\s+[^>]*|)>)(' . $recursiveDomRegex . '<\/form>)/xs';
		*/
		// NOTE: recursive regex will fail on action=edit since there would be too many recursions... lets build a simple one:
		$regex = '/(<form(?:\s+[^>]*|)>)()/s'; // $2 just to keep it compatible to recursive regex above when testing^^

		// the hidden input field we want to inject into each <form> field:
		$langInfo = \Html::hidden( 'uselang', $wgLang->getCode() );

		// replacement contains the hidden input field
		$replacement = "\n" . '$1' . $langInfo . "\n" . '<!-- STTLanguage hacked this form -->' . '$2';

		// replace <form>'s content with content + hidden 'uselang' field
		echo preg_replace( $regex, $replacement, $output );
		echo sprintf( '<!-- STTLanguage <form> \'uselang\' injection done in  %01.3f secs -->', microtime() - $startTime ) . "\n";

		return true;
	}
}
