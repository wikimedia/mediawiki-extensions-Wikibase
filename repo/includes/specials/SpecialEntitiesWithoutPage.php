<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\EntityFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Store\EntityPerPage;
use XmlSelect;

/**
 * Base page for pages listing entities without a specific value.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialEntitiesWithoutPage extends SpecialWikibaseQueryPage {

	/**
	 * The language used
	 *
	 * @var string
	 */
	private $language = '';

	/**
	 * The type used
	 *
	 * @var string|null
	 */
	private $type = null;

	/**
	 * @var string
	 */
	private $termType;

	/**
	 * @var string
	 */
	private $legendMsg;

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	public function __construct(
		$name,
		$termType,
		$legendMsg,
		EntityPerPage $entityPerPage,
		EntityFactory $entityFactory,
		ContentLanguages $termsLanguages
	) {
		parent::__construct( $name );

		$this->termType = $termType;
		$this->legendMsg = $legendMsg;
		$this->entityPerPage = $entityPerPage;
		$this->entityFactory = $entityFactory;
		$this->termsLanguages = $termsLanguages;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.4
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->prepareArguments( $subPage );
		$this->setForm();

		if ( $this->language !== '' ) {
			$this->showQuery();
		}
	}

	/**
	 * Prepare the arguments
	 *
	 * @param string $subPage
	 */
	private function prepareArguments( $subPage ) {
		$request = $this->getRequest();

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
		if ( $this->language !== '' && !$this->termsLanguages->hasLanguage( $this->language ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-entitieswithoutlabel-invalid-language', $this->language )->parse() );
			$this->language = '';
		}

		$this->type = $request->getText( 'type', $this->type );
		if ( $this->type === '' ) {
			$this->type = null;
		}
		if ( $this->type !== null && !$this->entityFactory->isEntityType( $this->type ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-entitieswithoutlabel-invalid-type', $this->type )->parse() );
			$this->type = null;
		}
	}

	/**
	 * Build the HTML form
	 */
	private function setForm() {
		$typeSelect = new XmlSelect( 'type', 'wb-entitieswithoutpage-type', $this->type );
		$typeSelect->addOption( $this->msg( 'wikibase-entitieswithoutlabel-label-alltypes' )->text(), '' );
		foreach ( $this->entityFactory->getEntityTypes() as $type ) {
			// Messages: wikibase-entity-item, wikibase-entity-property, wikibase-entity-query
			$typeSelect->addOption( $this->msg( 'wikibase-entity-' . $type )->text(), $type );
		}

		$this->getOutput()->addModules( 'wikibase.special.entitiesWithout' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $this->getPageTitle()->getLocalURL(),
					'name' => 'entitieswithoutpage',
					'id' => 'wb-entitieswithoutpage-form'
				)
			) .
			Html::input (
				'title',
				$this->getPageTitle()->getPrefixedText(),
				'hidden',
				array()
			) .
			Html::openElement( 'fieldset' ) .
			Html::element(
				'legend',
				array(),
				$this->msg( $this->legendMsg )->text()
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
		return $this->entityPerPage->getEntitiesWithoutTerm( $this->termType, $this->language, $this->type, $limit, $offset );
	}


	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.4
	 */
	protected function getTitleForNavigation() {
		return $this->getPageTitle( $this->language . '/' . $this->type );
	}

}
