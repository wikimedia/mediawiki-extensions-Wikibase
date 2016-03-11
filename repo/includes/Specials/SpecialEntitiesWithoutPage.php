<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Store\EntityPerPage;

/**
 * Base page for pages listing entities without a specific value.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
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
	 * @var string One of the TermIndexEntry::TYPE_... constants.
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
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @param string $name
	 * @param string $termType One of the TermIndexEntry::TYPE_... constants.
	 * @param string $legendMsg
	 * @param EntityPerPage $entityPerPage
	 * @param string[] $entityTypes
	 * @param ContentLanguages $termsLanguages
	 */
	public function __construct(
		$name,
		$termType,
		$legendMsg,
		EntityPerPage $entityPerPage,
		array $entityTypes,
		ContentLanguages $termsLanguages
	) {
		parent::__construct( $name );

		$this->termType = $termType;
		$this->legendMsg = $legendMsg;
		$this->entityPerPage = $entityPerPage;
		$this->entityTypes = $entityTypes;
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
		if ( $this->type !== null && !in_array( $this->type, $this->entityTypes ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-entitieswithoutlabel-invalid-type', $this->type )->parse() );
			$this->type = null;
		}
	}

	/**
	 * Build the HTML form
	 */
	private function setForm() {
		$options = array(
			$this->msg( 'wikibase-entitieswithoutlabel-label-alltypes' )->text() => ''
		);

		foreach ( $this->entityTypes as $type ) {
			// Messages: wikibase-entity-item, wikibase-entity-property, wikibase-entity-query
			$options[$this->msg( 'wikibase-entity-' . $type )->text()] = $type;
		}

		$this->getOutput()->addModules( 'wikibase.special.languageSuggester' );

		$formDescriptor = array(
			'language' => array(
				'name' => 'language',
				'default' => $this->language,
				'type' => 'text',
				'cssclass' => 'wb-language-suggester',
				'id' => 'wb-entitieswithoutpage-language',
				'label-message' => 'wikibase-entitieswithoutlabel-label-language'
			),
			'type' => array(
				'name' => 'type',
				'options' => $options,
				'default' => $this->type,
				'type' => 'select',
				'id' => 'wb-entitieswithoutpage-type',
				'label-message' => 'wikibase-entitieswithoutlabel-label-type'
			),
			'submit' => array(
				'name' => '',
				'default' => $this->msg( 'wikibase-entitieswithoutlabel-submit' )->text(),
				'type' => 'submit',
				'id' => 'wikibase-entitieswithoutpage-submit',
			)
		);

		HTMLForm::factory( 'inline', $formDescriptor, $this->getContext() )
			->setId( 'wb-entitieswithoutpage-form' )
			->setMethod( 'get' )
			->setWrapperLegendMsg( $this->legendMsg )
			->suppressDefaultSubmit()
			->setSubmitCallback( function () {// no-op
			} )->show();
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return EntityId[]
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
