<?php

/**
 * Handles the view action for Wikibase items.
 *
 * TODO: utilized CachedAction once in core
 *
 * @since 0.1
 *
 * @file WikibaseViewItemAction.php
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseViewItemAction extends FormlessAction {

	/**
	 * (non-PHPdoc)
	 * @see Action::getName()
	 */
	public function getName() {
		return 'view';
	}

	/**
	 * (non-PHPdoc)
	 * @see FormlessAction::onView()
	 */
	public function onView() {
		$content = $this->getContext()->getWikiPage()->getContent();

		if ( is_null( $content ) ) {
			// TODO: show ui for editing an empty item that does not have an ID yet.
		}
		else {
			// TODO: switch on type of content.
			$contentLangCode = $this->getLanguage()->getCode();

			$parserOutput = $content->getParserOutput( $this->getContext() );

			$out = $this->getOutput();

			$out->addHTML( $parserOutput->getText() );

			// make sure required client sided resources will be loaded:
			$out->addModules( 'wikibase' );

			// overwrite page title
			$out->setPageTitle( $content->getLabel( $contentLangCode ) );

			// hand over the itemId to JS
			$out->addJsConfigVars( 'wbItemId', $content->getId() );
			$out->addJsConfigVars( 'wbDataLangName', Language::fetchLanguageName( $contentLangCode ) );
			
			// TODO get this from the configuration after its implemented:
			$dummySiteDetails = array(
				'en' => array(
					'shortName' => 'English',
					'name' => 'English Wikipedia',
					'pageUrl' => 'http://en.wikipedia.org/wiki/$1',
					'apiUrl' => 'http://en.wikipedia.org/w/api.php' // NOTE: we might better have an internal API module instead of using the site APIs directly
				),
				'de' => array(
					'shortName' => 'German', // name in users language
					'name' => 'German Wikipedia', // name in users language
					'pageUrl' => 'http://de.wikipedia.org/wiki/$1',
					'apiUrl' => 'http://de.wikipedia.org/w/api.php'
				),
				'he' => array(
					'shortName' => 'Hebrew',
					'name' => 'Hebrew Wikipedia',
					'pageUrl' => 'http://he.wikipedia.org/wiki/$1',
					'apiUrl' => 'http://he.wikipedia.org/w/api.php'
				),
				'ja' => array(
					'shortName' => 'Japanese',
					'name' => 'Japanese Wikipedia',
					'pageUrl' => 'http://ja.wikipedia.org/wiki/$1',
					'apiUrl' => 'http://ja.wikipedia.org/w/api.php'
				)
			);
			
			$out->addJsConfigVars( 'wbSiteDetails', $dummySiteDetails );
		}

		return '';
	}

	/**
	 * (non-PHPdoc)
	 * @see Action::getDescription()
	 */
	protected function getDescription() {
		return '';
	}

}