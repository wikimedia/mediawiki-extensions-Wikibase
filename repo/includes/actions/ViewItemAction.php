<?php

namespace Wikibase;
use Language;

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
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
class ViewItemAction extends \FormlessAction {

	/**
	 * (non-PHPdoc)
	 * @see Action::getName()
	 */
	public function getName() {
		return 'view';
	}

	public function onView() {
		$article = \Article::newFromTitle( $this->getTitle(), $this->getContext() );
		$article->view();

		/* @var Item $item */
		$item = $article->getContentObject();
		$out = $this->getContext()->getOutput();
		$langCode = $this->getContext()->getLanguage()->getCode();
		$label = $item->getLabel( $this->getLanguage()->getCode() );

		if ( $this->getContext()->getRequest()->getCheck( 'diff' ) ) {
			$out->setPageTitle( $this->msg( 'difference-title', $label ) );
		} else {
			//FIXME: we may be overriding an error page title here, or something else we don't know about.
			//       Using the label should just be the default.
			$this->getOutput()->setPageTitle( $label );
		}

		// hand over the itemId to JS
		$out->addJsConfigVars( 'wbItemId', $item->getId() );
		$out->addJsConfigVars( 'wbDataLangName', Language::fetchLanguageName( $langCode ) );

		// TODO: this whole construct doesn't really belong here:
		$sites = array();

		foreach ( Sites::singleton()->getGroup( SITE_GROUP_WIKIPEDIA ) as  /** @var \Wikibase\Site $site */ $site ) {
			$sites[$site->getConfig()->getLocalId()] = array(
				'shortName' => \Language::fetchLanguageName( $site->getConfig()->getLocalId() ),
				'name' => \Language::fetchLanguageName( $site->getConfig()->getLocalId() ), // TODO: names should be configurable in settings
				'pageUrl' => $site->getPagePath(),
				'apiUrl' => $site->getFilePath( 'api.php' ),
			);
		}
		$out->addJsConfigVars( 'wbSiteDetails', $sites );

	}

	/**
	 * (non-PHPdoc)
	 * @see Action::getDescription()
	 */
	protected function getDescription() {
		return '';
	}

}