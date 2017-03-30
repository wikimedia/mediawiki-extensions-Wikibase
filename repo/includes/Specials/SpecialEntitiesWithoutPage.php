<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\Store\EntitiesWithoutTermFinder;

/**
 * Base page for pages listing entities without a specific value.
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
	 * @var EntitiesWithoutTermFinder
	 */
	private $entitiesWithoutTerm;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param string $name
	 * @param string $termType One of the TermIndexEntry::TYPE_... constants.
	 * @param string $legendMsg
	 * @param EntitiesWithoutTermFinder $entitiesWithoutTerm
	 * @param string[] $entityTypes
	 * @param ContentLanguages $termsLanguages
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		$name,
		$termType,
		$legendMsg,
		EntitiesWithoutTermFinder $entitiesWithoutTerm,
		array $entityTypes,
		ContentLanguages $termsLanguages,
		LanguageNameLookup $languageNameLookup
	) {
		parent::__construct( $name );

		$this->termType = $termType;
		$this->legendMsg = $legendMsg;
		$this->entitiesWithoutTerm = $entitiesWithoutTerm;
		$this->entityTypes = $entityTypes;
		$this->termsLanguages = $termsLanguages;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->prepareArguments( $subPage );
		$this->setForm();

		if ( $this->language !== '' && $this->type !== '' ) {
			$this->showQuery();
		}
	}

	/**
	 * Prepare the arguments
	 *
	 * @param string|null $subPage
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
			$this->showErrorHTML( $this->msg(
				'wikibase-entitieswithoutlabel-invalid-language',
				wfEscapeWikiText( $this->language )
			)->parse() );
			$this->language = '';
		}

		$this->type = $request->getText( 'type', $this->type );
		if ( $this->type !== '' && !in_array( $this->type, $this->entityTypes ) ) {
			$this->showErrorHTML( $this->msg(
				'wikibase-entitieswithoutlabel-invalid-type',
				wfEscapeWikiText( $this->type )
			)->parse() );
			$this->type = '';
		}
	}

	/**
	 * Return options for the language input field.
	 *
	 * @return array
	 */
	private function getLanguageOptions() {
		$options = array();
		foreach ( $this->termsLanguages->getLanguages() as $languageCode ) {
			$languageName = $this->languageNameLookup->getName( $languageCode );
			$options["$languageName ($languageCode)"] = $languageCode;
		}
		return $options;
	}

	/**
	 * Build the HTML form
	 */
	private function setForm() {
		$options = array();

		foreach ( $this->entityTypes as $type ) {
			// Messages: wikibase-entity-item, wikibase-entity-property
			$options[$this->msg( 'wikibase-entity-' . $type )->text()] = $type;
		}

		if ( $this->type !== null && $this->type !== '' ) {
			$defaultType = $this->type;
		} else {
			$defaultType = reset( $this->entityTypes );
		}

		$formDescriptor = array(
			'language' => array(
				'name' => 'language',
				'default' => $this->language,
				'type' => 'combobox',
				'options' => $this->getLanguageOptions(),
				'cssclass' => 'wb-language-suggester',
				'id' => 'wb-entitieswithoutpage-language',
				'label-message' => 'wikibase-entitieswithoutlabel-label-language'
			),
			'type' => array(
				'name' => 'type',
				'options' => $options,
				'default' => $defaultType,
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

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
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
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		return $this->entitiesWithoutTerm->getEntitiesWithoutTerm(
			$this->termType,
			$this->language,
			[ $this->type ],
			$limit,
			$offset
		);
	}

	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 */
	protected function getTitleForNavigation() {
		return $this->getPageTitle( $this->language . '/' . $this->type );
	}

}
