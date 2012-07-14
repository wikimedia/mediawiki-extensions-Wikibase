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
		$type = $updater->getDB()->getType();

		if ( $type === 'mysql' || $type === 'sqlite' ) {
			$updater->addExtensionTable(
				'wb_items',
				dirname( __FILE__ ) . '/sql/Wikibase.sql'
			);
		}
		elseif ( $type === 'postgres' ) {
			$updater->addExtensionTable(
				'wb_items',
				dirname( __FILE__ ) . '/sql/Wikibase.sql'
			);
		}
		else {
			// TODO
		}

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

		$testFiles = array(
			'ItemMove',
			'EntityHandler',
			'ItemContent',
			'ItemDeletionUpdate',
			'ItemDiffView',
			'ItemHandler',
			'ItemMove',
			'ItemView',

			'api/ApiJSONP',
			'api/ApiJSONPComplete',
			'api/ApiLanguageAttribute',
			'api/ApiSetAliases',
			'api/ApiSetItem',
			'api/ApiSetSiteLink',
			'api/ApiEditPage',
			'api/ApiPermissions',
			'api/ApiBotEdit',

			'specials/SpecialCreateItem',
			'specials/SpecialItemByLabel',
			'specials/SpecialItemByTitle',
		);

		foreach ( $testFiles as $file ) {
			$files[] = dirname( __FILE__ ) . '/tests/phpunit/includes/' . $file . 'Test.php';
		}

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

		if ( array_key_exists( $title->getNamespace(), $wgNamespaceContentModels )
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
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableAliases.tests.js',
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
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.PersistentPromisor.tests.js',
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
		if ( $article->getContent()->getModel() === CONTENT_MODEL_WIKIBASE_ITEM ) {
			/**
			 * @var $newItem \Wikibase\Item
			 */
			$newItem = $article->getContent()->getItem();

			if ( is_null( $revision->getParentId() ) ) {
				$change = \Wikibase\ItemCreation::newFromItem( $newItem );
			}
			else {
				$change = \Wikibase\ItemChange::newFromItems(
					Revision::newFromId( $revision->getParentId() )->getContent()->getItem(),
					$newItem
				);
			}

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
	 * Occurs after the delete article request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @since 0.1
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param string $reason
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public static function onArticleDeleteComplete( WikiPage &$wikiPage, User &$user, $reason, $id ) {
		// This is a temporary hack since the archive table does not correctly have the data we need.
		// Once this is fixed this can go, and we can use the commented out code later in this method.
		if ( $wikiPage->getTitle()->getNamespace() !== WB_NS_DATA ) {
			return true;
		}

		$dbw = wfGetDB( DB_MASTER );

		$archiveEntry = $dbw->selectRow(
			'archive',
			array(
				'ar_user',
				'ar_text_id',
				'ar_rev_id',
				'ar_timestamp',
				'ar_content_format',
			),
			array(
				'ar_page_id' => $id,
				// 'ar_content_model' => CONTENT_MODEL_WIKIBASE_ITEM,
			),
			__METHOD__
		);

		if ( $archiveEntry !== false ) {
			$textEntry = $dbw->selectRow(
				'text',
				'old_text',
				array( 'old_id' => $archiveEntry->ar_text_id ),
				__METHOD__
			);

			if ( $textEntry !== false ) {
				$itemHandler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
				$itemContent = $itemHandler->unserializeContent( $textEntry->old_text/* , $archiveEntry->ar_content_format */ );
				$item = $itemContent->getItem();
				$change = \Wikibase\ItemDeletion::newFromItem( $item );

				$change->setFields( array(
					'revision_id' => $archiveEntry->ar_rev_id,
					'user_id' => $archiveEntry->ar_user,
					'object_id' => $item->getId(),
					'time' => $archiveEntry->ar_timestamp,
				) );

				\Wikibase\ChangeNotifier::singleton()->handleChange( $change );
			}
		}

		return true;
	}

	/**
	 * Called when somebody tries to edit an item directly through the API.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/APIEditBeforeSave
	 *
	 * @since 0.1
	 * @param EditPage $editPage: the EditPage object
	 * @param string $text: the new text of the article (has yet to be saved)
	 * @param array $resultArr: data in this array will be added to the API result
	 */
	public static function onAPIEditBeforeSave( EditPage $editPage, string $text, array &$resultArr ) {
		if ( $editPage->getTitle()->getContentModel() === CONTENT_MODEL_WIKIBASE_ITEM ) {
			return false;
		}
		else {
			return true;
		}
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

				// Defaults to turn on deletion of empty items
				// set to true will always delete empty items
				'apiDeleteEmpty' => false,

				// Defaults to turn off use of keys
				// set to true will always return the key form
				'apiUseKeys' => true,

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

				// Which messages to use while formating logs
				'apiFormatMessages' => array(
					'languages' => array(
						'set-set-language-attributes' => 'wikibase-api-summary-set-language-attributes',
						'set-remove-language-attributes' => 'wikibase-api-summary-set-remove-language-attributes',
						'remove-set-language-attributes' => 'wikibase-api-summary-remove-set-language-attributes',
						'remove-remove-language-attributes' => 'wikibase-api-summary-remove-remove-language-attributes',
						'set-language-label' => 'wikibase-api-summary-set-language-label',
						'remove-language-label' => 'wikibase-api-summary-remove-language-label',
						'set-language-description' => 'wikibase-api-summary-set-language-description',
						'remove-language-description' => 'wikibase-api-summary-remove-language-description',
						'set-add-aliases' => 'wikibase-api-summary-set-add-aliases',
						'set-remove-aliases' => 'wikibase-api-summary-set-remove-aliases',
						'set-aliases' => 'wikibase-api-summary-set-aliases',
						'add-aliases' => 'wikibase-api-summary-add-aliases',
						'remove-aliases' => 'wikibase-api-summary-remove-aliases',
					),
					'sites' => array(
						'set-sitelink' => 'wikibase-api-summary-set-sitelink',
						'remove-sitelink' => 'wikibase-api-summary-remove-sitelink',
					),
				),
				// settings for the user agent
				//TODO: This should REALLY be handled somehow as without it we could run into lots of trouble
				'clientTimeout' => 10, // this is before final timeout, without maxlag or maxage we can't hang around
				//'clientTimeout' => 120, // this is before final timeout, the maxlag value and then some
				'clientPageOpts' => array(
					'userAgent' => 'Wikibase',
				),
				'clientPageArgs' => array(
					'action' => 'query',
					'prop' => 'info',
					'redirects' => true,
					'converttitles' => true,
					'format' => 'json',
					//TODO: This should REALLY be handled somehow as without it we could run into lots of trouble
					//'maxage' => 5, // filter down repeated clicks, don't let clicky folks loose to fast
					//'smaxage' => 15, // give the proxy some time, don't let clicky folks loose to fast
					//'maxlag' => 100, // time to wait on a lagging server, hanging on for 100 sec is very aggressive
				),
			)
		);

		return true;
	}

	/**
	 * Alter the structured navigation links in SkinTemplates.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 *
	 * @since 0.1
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 *
	 * @return boolean
	 */
	public static function onPageTabs( SkinTemplate &$sktemplate, array &$links ) {
		if ( in_array( $sktemplate->getTitle()->getContentModel(), array( CONTENT_MODEL_WIKIBASE_ITEM ) ) ) {
			unset( $links['views']['edit'] );
		}

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
		global $wgLang;
//return true;
		// Note that it can be necessary to check the title object and/or item before any
		// other code is run in this callback. If it is possible to avoid loading the whole
		// page then the code will be lighter on the server. Present code formats autocomment
		// after detecting a legal message key, and without using the title or page.

		if ( preg_match( '/^([\-\w]+):(.*)$/', $auto, $matches ) ) {
			// a helper function for language names
			$fetchLangName = function ( $code ) {
				global $wgLang;
				return Language::fetchLanguageName( $code, $wgLang->getCode() );
			};

			// a helper function for site names
			$fetchSiteName = function ( $code ) {
				global $wgLang;
				$site = \Wikibase\Sites::singleton()->getSiteByGlobalId( $code );
				return isset($site) ? Language::fetchLanguageName( $site->getLanguage(), $wgLang->getCode() ) : $code;
			};

			// then we should check each initial part if it is a key in the array
			foreach ( \Wikibase\Settings::get( 'apiFormatMessages' ) as $key => $messages ) {

				// if it matches one key we can procede
				if ( isset( $messages[$matches[1]] ) ) {

					// turn the args to the message into an array
					$args = explode( '|', $matches[2] );

					// turn the first arg into a list in the user language
					if ( $key === 'languages' ) {
						$list = array_map( $fetchLangName, explode( '¦', array_shift( $args ) ) );
					}
					elseif ( $key === 'sites' ) {
						$list = array_map( $fetchSiteName, explode( '¦', array_shift( $args ) ) );
					}
					else {
						$this->dieUsage( wfMsg( 'wikibase-error-not-recognized' ), 'error-not-recognized' );
					}

					// build the containing message
					$auto = wfMessage( $messages[$matches[1]] )
						->params( array_merge( array( count( $list ), $wgLang->commaList( $list ) ), $args ) )
						->escaped();

					if ( $pre ) {
						# written summary $presep autocomment (summary /* section */)
						$pre .= wfMessage( 'autocomment-prefix' )->escaped();
					}
					if ( $post ) {
						# autocomment $postsep written summary (/* section */ summary)
						$auto .= wfMessage( 'colon-separator' )->escaped();
					}

					$auto = '<span class="autocomment">' . $auto . '</span>';
					$comment = $pre . $wgLang->getDirMark() . '<span dir="auto">' . $auto . $post . '</span>';

					// don't bother with a second pass if a hit was found in the first one
					break;
				}
			}
		}
		return true;
	}

	/**
	 * Used to append a css class to the body, so the page can be identified as Wikibase item page.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBodyAttributes
	 *
	 * @since 0.1
	 *
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @param array $bodyAttrs
	 *
	 * @return bool
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $sk, array &$bodyAttrs ) {
		if ( $out->getTitle()->getContentModel() === CONTENT_MODEL_WIKIBASE_ITEM ) {
			// we only add the classes, if there is an actual item and not just an empty Page in the right namespace
			$itemPage = new WikiPage( $out->getTitle() );
			$itemContent = $itemPage->getContent();

			if( $itemContent !== null ) {
				// add class to body so it's clear this is a wb item:
				$bodyAttrs['class'] .= ' wb-itempage';
				// add another class with the ID of the item:
				$bodyAttrs['class'] .= ' wb-itempage-' . $itemContent->getItem()->getId();
			}
		}
		return true;
	}

	/**
	 * Special page handling where we want to display meaningful link labels instead of just the items ID.
	 * This is only handling special pages right now and gets disabled in normal pages.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinkBegin
	 *
	 * @param DummyLinker $skin
	 * @param Title $target
	 * @param string $text
	 * @param array $customAttribs
	 * @param string $query
	 * @param array $options
	 * @param mixed $ret
	 * @return bool true
	 */
	public static function onLinkBegin( $skin, $target, &$html, array &$customAttribs, &$query, &$options, &$ret ) {
		if(
			// if custom link text is given, there is no point in overwriting it
			$html !== null
			// we only want to handle links to data items differently here
			|| $target->getContentModel() !== CONTENT_MODEL_WIKIBASE_ITEM
			// as of MW 1.20 Linker shouldn't support anything but Title anyhow
			|| ! $target instanceof Title
		) {
			return true;
		}

		// $wgTitle is temporarily set to special pages Title in case of special page inclusion! Therefore we can
		// just check whether the page is a special page and if not, disable the behavior.
		global $wgTitle;

		if( ! $wgTitle->isSpecialPage() ) {
			// no special page, we don't handle this for now
			// NOTE: If we want to handle this, messages would have to be generated in sites language instead of
			//       users language so they are cache independent.
			return true;
		}

		global $wgLang, $wgOut;

		// If this fails we will not find labels and descriptions later
		$lang = $wgLang->getCode();

		// The following three vars should all exist, unless there is a failurre
		// somewhere, and then it will fail hard. Better test it now!
		$page = new WikiPage( $target );
		if ( is_null( $page ) ) {
			// failed, can't continue
			// this should not happen
			return true;
		}
		$content = $page->getContent();
		if ( is_null( $content ) ) {
			// failed, can't continue
			// this could happen because the content is empty (page doesn't exist), e.g. after item was deleted
			return true;
		}
		$item = $content->getItem();
		if ( is_null( $item ) ) {
			// failed, can't continue
			// this could happen because there is an illegal structure that could not be parsed
			return true;
		}

		$rawLabel = $item->getLabel( $lang );
		$rawDescription = $item->getDescription( $lang );

		// construct link:
		$idHtml = '<span class="wb-itemlink-id">'
			. wfMsgForContent( 'wikibase-itemlink-id-wrapper', htmlspecialchars( 'q' . $item->getId() ) )
			. '</span>';
		$labelHtml = '<span class="wb-itemlink-label">'
			. htmlspecialchars( $rawLabel )
			. '</span>';

		$html =  '<span class="wb-itemlink">' . wfMsgForContent( 'wikibase-itemlink', $labelHtml, $idHtml ) . '</span>';

		// set title attribute for constructed link:
		$titleText = ( $rawLabel !== false ) ? $rawLabel : $target->getPrefixedText();
		$customAttribs[ 'title' ] = ( $rawDescription !== false )
			? wfMsgForContent( 'wikibase-itemlink-title', $titleText, $rawDescription )
			: $titleText; // no description, just display the title then

		// add wikibase styles in all cases, so we can format the link properly:
		$wgOut->addModuleStyles( array( 'wikibase.common' ) );

		return true;
	}

}
