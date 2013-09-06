<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\EntityFactory;
use Wikibase\Lib\Specials\SpecialWikibaseQueryPage;
use Wikibase\StoreFactory;
use Wikibase\Utils;
use XmlSelect;

/**
 * Base page for pages listing entities without a specific value.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Bene*
 */
abstract class SpecialEntitiesWithoutPage extends SpecialWikibaseQueryPage {

	/**
	 * The language used
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language = '';

	/**
	 * The type used
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $type = null;

	/**
	 * Map entity types to objects representing the corresponding entity
	 *
	 * @since 0.4
	 *
	 * @var array
	 */
	protected $possibleTypes;

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.4
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->prepareArguments( $subPage );

		$this->setForm();

		if ( $this->language !== '' ) {
			$this->showQuery();
		}
	}

	/**
	 * Prepare the arguments
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	private function prepareArguments( $subPage ) {
		$request = $this->getRequest();
		$output = $this->getOutput();

		$this->language = '';
		$this->type = null;
		if ( $subPage !== null ) {
			$parts = explode( '/', $subPage );
			if ( isset( $parts[1] ) ) {
				$this->type = $parts[1];
			}
			$this->language = $parts[0];
		}

		$this->language = $request->getText( 'language', $this->language );
		if ( $this->language !== '' && !in_array( $this->language, Utils::getLanguageCodes() ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-entitieswithoutlabel-invalid-language', $this->language )->parse() );
			$this->language = '';
		}

		$this->type = $request->getText( 'type', $this->type );
		$this->possibleTypes = EntityFactory::singleton()->getEntityTypes();
		if ( $this->type === '' ) {
			$this->type = null;
		}
		if ( $this->type !== null && !in_array( $this->type, $this->possibleTypes ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-entitieswithoutlabel-invalid-type', $this->type )->parse() );
			$this->type = null;
		}
	}

	/**
	 * Show an error
	 *
	 * @since 0.4
	 *
	 * @param string $error The error message in HTML format
	 * @param string $class The element's class, default 'error'
	 */
	private function showErrorHTML( $error, $class = 'error' ) {
		$this->getOutput()->addHTML(
			Html::rawElement(
				'p',
				array( 'class' => $class ),
				$error
			)
		);
	}

	/**
	 * Build the HTML form
	 *
	 * @since 0.4
	 */
	private function setForm() {
		$typeSelect = new XmlSelect( 'type', 'wb-entitieswithoutpage-type', $this->type );
		$typeSelect->addOption( $this->msg( 'wikibase-entitieswithoutlabel-label-alltypes' )->text(), '' );
		// item, property and query
		foreach( $this->possibleTypes as $type ) {
			$typeSelect->addOption( $this->msg( 'wikibase-entity-' . $type )->text(), $type );
		}

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $this->getTitle()->getLocalURL(),
					'name' => 'entitieswithoutpage',
					'id' => 'wb-entitieswithoutpage-form'
				)
			) .
			Html::openElement( 'fieldset' ) .
			Html::element(
				'legend',
				array(),
				$this->getLegend()
			) .
			Html::openElement( 'p' ) .
			Html::element(
				'label',
				array(
					'for' => 'wb-entitieswithoutpage-language'
				),
				$this->msg( 'wikibase-entitieswithoutlabel-label-language' )->text()
			) . ' ' .
			Html::input(
				'language',
				$this->language,
				'text',
				array(
					'id' => 'wb-entitieswithoutpage-language'
				)
			) . ' ' .
			Html::element(
				'label',
				array(
					'for' => 'wb-entitieswithoutpage-type'
				),
				$this->msg( 'wikibase-entitieswithoutlabel-label-type' )->text()
			) . ' ' .
			$typeSelect->getHTML() . ' ' .
			Html::input(
				'submit',
				$this->msg( 'wikibase-entitieswithoutlabel-submit' )->text(),
				'submit',
				array(
					'id' => 'wikibase-entitieswithoutpage-submit',
					'class' => 'wb-input-button'
				)
			) .
			Html::closeElement( 'p' ) .
			Html::closeElement( 'fieldset' ) .
			Html::closeElement( 'form' )
		);
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		return $entityPerPage->getEntitiesWithoutTerm( $this->getTermType(), $this->language, $this->type, $limit, $offset );
	}


	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.4
	 */
	protected function getTitleForNavigation() {
		return $this->getTitle( $this->language . '/' . $this->type );
	}

	/**
	 * Get the term type (member of Term::TYPE_ enum)
	 *
	 * @since 0.4
	 */
	protected abstract function getTermType();

	/**
	 * Get the legend in HTML format
	 *
	 * @since 0.4
	 */
	protected abstract function getLegend();

}
