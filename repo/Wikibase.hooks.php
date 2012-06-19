<?php

/**
 * File defining the hook handlers for the Wikibase Repo extension.
 *
 * @since 0.1
 *
 * @file Wikibase.hooks.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nikola Smolenski
 * @author Daniel Werner
 */
final class WikibaseHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return boolean
	 */
	public static function onSchemaUpdate( DatabaseUpdater $updater ) {
		$updater->addExtensionTable(
			'wb_items',
			dirname( __FILE__ ) . '/sql/Wikibase.sql'
		);

		$updater->addExtensionTable(
			'wb_aliases',
			dirname( __FILE__ ) . '/sql/AddAliasesTable.sql'
		);

		return true;
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.1
	 *
	 * @param array &$files
	 *
	 * @return boolean
	 */
	public static function registerUnitTests( array &$files ) {
		$testDir = dirname( __FILE__ ) . '/tests/phpunit/includes/';

		$files[] = $testDir . 'ItemMoveTest.php';
		$files[] = $testDir . 'ChangeNotifierTest.php';
		$files[] = $testDir . 'EntityHandlerTest.php';
		$files[] = $testDir . 'ItemDeletionUpdateTest.php';
		$files[] = $testDir . 'ItemHandlerTest.php';
		$files[] = $testDir . 'ItemViewTest.php';
		$files[] = $testDir . 'UtilsTest.php';

		// api
		$files[] = $testDir . 'api/ApiJSONPTests.php';
		$files[] = $testDir . 'api/ApiLanguageAttributeTest.php';
		$files[] = $testDir . 'api/ApiSetAliasesTest.php';
		$files[] = $testDir . 'api/ApiSetItemTests.php';

		return true;
	}

	/**
	 * In Wikidata namespace, page content language is the same as the current user language.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentLanguage
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param Language &$pageLanguage
	 * @param Language|StubUserLang $language
	 *
	 * @return boolean
	 */
	public static function onPageContentLanguage( Title $title, Language &$pageLanguage, $language ) {
		global $wgNamespaceContentModels;

		if( array_key_exists( $title->getNamespace(), $wgNamespaceContentModels )
			&& $wgNamespaceContentModels[$title->getNamespace()] === CONTENT_MODEL_WIKIBASE_ITEM ) {
			$pageLanguage = $language;
		}

		return true;
	}

	/**
	 * Add new javascript testing modules. This is called after the addition of MediaWiki core test suites.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @since 0.1
	 *
	 * @param array &$testModules
	 * @param ResourceLoader &$resourceLoader
	 *
	 * @return boolean
	 */
	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['wikibase.tests'] = array(
			'scripts' => array(
				'tests/qunit/wikibase.tests.js',
				'tests/qunit/wikibase.Site.tests.js',
				'tests/qunit/wikibase.ui.DescriptionEditTool.tests.js',
				'tests/qunit/wikibase.ui.LabelEditTool.tests.js',
				'tests/qunit/wikibase.ui.SiteLinksEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableDescription.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableLabel.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableSiteLink.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.Interface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.ListInterface.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.EditGroup.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Group.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Label.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Button.tests.js',
				'tests/qunit/wikibase.ui.Tooltip.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.inputAutoExpand.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tagadata.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.eachchange.tests.js',
			),
			'dependencies' => array(
				'wikibase.tests.qunit.testrunner',
				'wikibase',
				'wikibase.utilities.jQuery',
				'wikibase.ui.Toolbar',
				'wikibase.ui.PropertyEditTool'
			),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => 'Wikibase/repo',
		);

		return true;
	}

	/**
	 * Allows overriding if the pages in a certain namespace can be moved or not.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NamespaceIsMovable
	 *
	 * @since 0.1
	 *
	 * @param integer $index
	 * @param boolean $movable
	 *
	 * @return boolean
	 */
	public static function onNamespaceIsMovable( $index, &$movable ) {
		if ( in_array( $index, array( WB_NS_DATA, WB_NS_DATA_TALK ) ) ) {
			$movable = false;
		}

		return true;
	}

	/**
	 * Called when a revision was inserted due to an edit.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
	 *
	 * @since 0.1
	 *
	 * @param weirdStuffButProbablyWikiPage $article
	 * @param Revision $revision
	 * @param integer $baseID
	 * @param User $user
	 *
	 * @return boolean
	 */
	public static function onNewRevisionFromEditComplete( $article, Revision $revision, $baseID, User $user ) {
		$newItem = $article->getContent();

		if ( $newItem->getModel() === CONTENT_MODEL_WIKIBASE_ITEM ) {
			$oldItem = is_null( $revision->getParentId() ) ? Wikibase\Item::newEmpty() : Revision::newFromId( $revision->getParentId() )->getContent();

			$change = \Wikibase\ItemChange::newFromItems( $oldItem, $newItem );

			$change->setFields( array(
				'revision_id' => $revision->getId(),
				'user_id' => $user->getId(),
				'object_id' => $newItem->getId(),
				'time' => $revision->getTimestamp(),
			) );

			\Wikibase\ChangeNotifier::singleton()->handleChange( $change );
		}

		return true;
	}

	/**
	 * Allows to add user preferences.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * NOTE: Might make sense to put the inner functionality into a well structured Preferences file once this
	 *       becomes more.
	 *
	 * @since 0.1
	 *
	 * @param User $user
	 * @param array &$preferences
	 *
	 * @return bool
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['wb-languages'] = array(
			'type' => 'multiselect',
			'usecheckboxes' => false,
			'label-message' => 'wikibase-setting-languages',
			'options' => $preferences['language']['options'], // all languages available in 'language' selector
			'section' => 'personal/i18n',
			'prefix' => 'wb-languages-',
		);

		return true;
	}

	/**
	 * Called after fetching the core default user options.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGetDefaultOptions
	 *
	 * @param array &$defaultOptions
	 *
	 * @return bool
	 */
	public static function onUserGetDefaultOptions( array &$defaultOptions ) {
		// pre-select default language in the list of fallback languages
		$defaultLang = $defaultOptions['language'];
		$defaultOptions[ 'wb-languages-' . $defaultLang ] = 1;

		return true;
	}

	/**
	 * Adds default settings.
	 * Setting name (string) => setting value (mixed)
	 *
	 * @param array &$settings
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onWikibaseDefaultSettings( array &$settings ) {
		$settings = array_merge(
			$settings,
			array(
				// alternative: application/vnd.php.serialized
				'serializationFormat' => CONTENT_FORMAT_JSON,

				// Defaults to turn off use of keys
				// set to true will always return the key form
				'apiUseKeys' => false,

				// Set API in debug mode
				// do not turn on in production!
				'apiInDebug' => false,

				// Additional settings for API when debugging is on to
				// facilitate testing, do not turn on in production!
				'apiDebugWithWrite' => true,
				'apiDebugWithPost' => false,
				'apiDebugWithRights' => false,
				'apiDebugWithTokens' => false,

				// Which formats to use with keys when there are a "usekeys" in the URL
				// undefined entries are interpreted as false
				'formatsWithKeys' => array(
					'json' => true,
					'jsonfm' => true,
					'php' => false,
					'phpfm' => false,
					'wddx' => false,
					'wddxfm' => false,
					'xml' => false,
					'xmlfm' => false,
					'yaml' => true,
					'yamlfm' => true,
					'raw' => true,
					'rawfm' => true,
					'txtfm' => true,
					'dbg' => true,
					'dbgfm' => true,
					'dump' => true,
					'dumpfm' => true,
				),
				// settings for the user agent
				'userAgent' => '$1 (Wikibase $2)',
				'clientTimeout' => 5,
				'clientPageOpts' => array(
					'action' => 'query',
					'prop' => 'info',
					'redirects' => true,
					'converttitles' => true,
					'format' => 'json',

				// Which messages to use while formating logs
				'apiFormatMessages' => array(
					'languages' => array(
						'delete-language-attributes' => 'wikibase-api-summary-delete-language-attributes',
						'delete-language-label' => 'wikibase-api-summary-delete-language-label',
						'delete-language-description' => 'wikibase-api-summary-delete-language-description',
						'delete-language-badge' => 'wikibase-api-summary-delete-language-badge',
						'change-aliases' => 'wikibase-api-summary-change-aliases',
						'set-aliases' => 'wikibase-api-summary-set-aliases',
						'remove-aliases' => 'wikibase-api-summary-remove-aliases',
						'add-aliases' => 'wikibase-api-summary-add-aliases',
						'set-language-attributes' => 'wikibase-api-summary-set-language-attributes',
						'set-language-label' => 'wikibase-api-summary-set-language-label',
						'set-language-description' => 'wikibase-api-summary-set-language-description',
						'set-language-badges' => 'wikibase-api-summary-set-language-badges',
					),
					'sites' => array(
						'add-sitelink' => 'wikibase-api-summary-add-sitelink',
						'update-sitelink' => 'wikibase-api-summary-update-sitelink',
						'set-sitelink' => 'wikibase-api-summary-set-sitelink',
						'remove-sitelink' => 'wikibase-api-summary-remove-sitelink',
					),
				),
			)
		);

		return true;
	}

	/**
	 * Pretty formating of autocomments.
	 * 
	 * @param string $comment reference to the finalized autocomment
	 * @param string $pre the string before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param string $post the string after the autocomment
	 * @param Titel $title use for further information
	 * @param boolean $local shall links be genreted locally or globally
	 */
	public static function onFormatAutocomments( $comment, $pre, $auto, $post, $title, $local ) {

		// Note that it can be necessary to check the title object and/or item before any
		// other code is run in this callback. If it is possible to avoid loading the whole
		// page then the code will be lighter on the server. Present code formats autocomment
		// after detecting a legal message key, and without using the title or page.

		// our first prerequisite to process this comment is to have the following form
		if ( preg_match( '/^([\-\w]+):(.*)$/', $auto, $matches ) ) {
			// then we should check each initial part if it is a key in the array
			$messages = \Wikibase\Settings::get( 'apiFormatMessages' );
			foreach ( $messages as $key => $msgs) {

				// if it matches one key we can procede
				if ( isset( $msgs[$matches[1]] ) ) {

					// keep the replacement message name for later..
					$msg = $msgs[$matches[1]];

					// and the messages used as wrappers and joiners for the head part
					$headWrapper = wfMessage( 'wikibase-api-summary-wrapper-' . $key );

					// and the messages used as wrappers and joiners for the tail part
					$tailWrapper = wfMessage( 'wikibase-api-summary-wrapper' );

					// turn our args into an array
					$args = explode( SUMMARY_GROUPING, $matches[2] );

					// and pop the head and format each element
					$f = function( $v ) use ( $headWrapper ) {
						$headmsg = clone $headWrapper;
						return $headmsg->params( trim($v) )->text();
					};
					$head = array_map( $f, explode( SUMMARY_SUBGROUPING, $args[0] ) );

					// make a unique list of the remaining args
					array_shift($args);
					$tail = array();
					$g = function( $v ) use ( $tailWrapper ) {
						$tailMessage = clone $tailWrapper;
						$v = trim( $v );
						// mb_ereg can't be anchored, so this is easier
						$str = null;
						if ( $v !== "" ) {
							$char = mb_substr( $v, mb_strlen( $v )-1, 1);
							if ( $char === SUMMARY_CONTINUATION ) {
								$str = $tailMessage->params( mb_substr( $v, 0, mb_strlen( $v ) - 1 ), $char )->text();
							}
							else {
								$str =  $tailMessage->params( $v, '' )->text();
							}
						}
						return $str ? $str : $tailMessage->params( '', '' )->text();
					};
					foreach ( $args as $arg ) {
						$tail = array_merge( $tail, array_map( $g, explode( SUMMARY_SUBGROUPING, $arg ) ) );
					}

					// build the core message
					$auto = wfMessage( $msg,
						count( $head ),
						$wgLang->commaList( $head ),
						count( $tail ),
						$wgLang->commaList( $tail ) )
						->escaped();

					if ( $pre ) {
						# written summary $presep autocomment (summary /* section */)
						$pre .= wfMessage( 'autocomment-prefix', array( 'escapenoentities', 'content' ) )->escaped();
					}

					if ( $post ) {
						# autocomment $postsep written summary (/* section */ summary)
						$auto .= wfMessage( 'colon-separator', array( 'escapenoentities', 'content' ) )->escaped();
					}

					$auto = '<span class="autocomment">' . $auto . '</span>';
					$comment = $pre . $wgLang->getDirMark() . '<span dir="auto">' . $auto . $post . '</span>';
				}
			}
		}
		return true;
	}

}
