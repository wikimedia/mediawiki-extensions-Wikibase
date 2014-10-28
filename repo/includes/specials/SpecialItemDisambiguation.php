<?php

namespace Wikibase\Repo\Specials;

use Html;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndex;

/**
 * Enables accessing items by providing the label of the item and the language of the label.
 * A result page is shown, disambiguating between multiple results if necessary.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialItemDisambiguation extends SpecialItemResolver {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var LabelLookup
	 */
	private $labelLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @see SpecialItemResolver::__construct
	 *
	 * @since 0.1
	 */
	public function __construct() {
		// args $name, $restriction, $listed
		parent::__construct( 'ItemDisambiguation', '', true );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getStore()->getTermIndex(),
			$wikibaseRepo->getEntityLookup(),
			$wikibaseRepo->getEntityTitleLookup()
		);

		//@todo: make this configurable
		$this->limit = 100;
	}

	/**
	 * Set service objects to use. Unit tests may call this to substitute mock
	 * services.
	 */
	public function initServices(
		TermIndex $termIndex,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->termIndex = $termIndex;
		$this->labelLookup = new LabelLookup( $termIndex, $this->getLanguage()->getCode() );
		$this->entityLookup = $entityLookup;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @see SpecialItemResolver::execute
	 *
	 * @since 0.1
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		// Setup
		$request = $this->getRequest();
		$parts = $subPage === '' ? array() : explode( '/', $subPage, 2 );
		$language = $request->getVal( 'language', isset( $parts[0] ) ? $parts[0] : '' );

		if ( $language === '' ) {
			$language = $this->getLanguage()->getCode();
		}

		if ( $request->getCheck( 'label' ) ) {
			$label = $request->getText( 'label' );
		}
		else {
			$label = isset( $parts[1] ) ? str_replace( '_', ' ', $parts[1] ) : '';
		}

		$this->switchForm( $language, $label );

		// Display the result set
		if ( isset( $language ) && isset( $label ) && $label !== '' ) {
			$items = $this->findLabelUsage(
				$language,
				$label
			);

			//@todo: show a message if count( $items ) > $this->limit.
			if ( 0 < count( $items ) ) {
				$this->getOutput()->setPageTitle( $this->msg( 'wikibase-disambiguation-title', $label )->escaped() );
				$this->displayDisambiguationPage( $items, $language );
			} else {
				$this->showNothingFound( $language, $label );
			}
		}

		return true;
	}

	/**
	 * Shows information, assuming no results were found.
	 *
	 * @param $language
	 * @param $label
	 */
	private function showNothingFound( $language, $label ) {
		// No results found
		if ( ( Language::isValidBuiltInCode( $language ) && ( Language::fetchLanguageName( $language ) !== "" ) ) ) {
			$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-nothing-found' );

			if ( $language === $this->getLanguage()->getCode() ) {
				$this->getOutput()->addWikiMsg(
					'wikibase-itemdisambiguation-search',
					urlencode( $label )
				);
				$this->getOutput()->addWikiMsg(
					'wikibase-itemdisambiguation-create',
					urlencode( $label )
				);
			}
		} else {
			// No valid language code
			$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-invalid-langcode' );
		}
	}

	/**
	 * Display disambiguation page.
	 *
	 * @since 0.1
	 *
	 * @param Item[] $items
	 * @param string $langCode
	 */
	protected function displayDisambiguationPage( array /* of Item */ $items, $langCode ) {
		$formatterOptions = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $this->getLanguage()->getCode()
		) );

		$linkFormatter = new EntityIdHtmlLinkFormatter(
			$formatterOptions,
			$this->labelLookup,
			$this->entityLookup,
			$this->entityTitleLookup
		);

		$disambiguationList = new ItemDisambiguation(
			$langCode,
			$this->getContext()->getLanguage()->getCode(),
			$linkFormatter
		);

		$html = $disambiguationList->getHTML( $items );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Output a form to allow searching for labels
	 *
	 * @since 0.1
	 *
	 * @param string|null $langCode
	 * @param string|null $label
	 */
	protected function switchForm( $langCode, $label ) {
		$this->getOutput()->addModules( 'wikibase.special.itemDisambiguation' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => 'itemdisambiguation',
					'id' => 'wb-itemdisambiguation-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-itemdisambiguation-lookup-fieldset' )->text()
			)
			. Html::element(
				'label',
				array( 'for' => 'wb-itemdisambiguation-languagename' ),
				$this->msg( 'wikibase-itemdisambiguation-lookup-language' )->text()
			)
			. Html::input(
				'language',
				$langCode ? $langCode : '',
				'text',
				array(
					'id' => 'wb-itemdisambiguation-languagename',
					'size' => 12,
					'class' => 'wb-input-text'
				)
			)
			. ' '
			. Html::element(
				'label',
				array( 'for' => 'labelname' ),
				$this->msg( 'wikibase-itemdisambiguation-lookup-label' )->text()
			)
			. Html::input(
				'label',
				$label ? $label : '',
				'text',
				array(
					'id' => 'labelname',
					'size' => 36,
					'class' => 'wb-input-text',
					'autofocus'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-itemdisambiguation-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-itembytitle-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

	/**
	 * Finds items that use the given label in the given language.
	 *
	 * @todo: Make this use an EntityInfoBuilder or similar instead of loading full entities.
	 * @todo: Should search over aliases as well, not just labels! Needs smart display though...
	 *
	 * @param string $language
	 * @param string $label
	 *
	 * @return Item[]
	 */
	private function findLabelUsage( $language, $label ) {
		$entityIds = $this->termIndex->getEntityIdsForLabel( $label, $language, Item::ENTITY_TYPE, true );
		$entities = array();

		$count = 0;

		foreach ( $entityIds as $entityId ) {
			$entity = $this->entityLookup->getEntity( $entityId );

			if ( $entity !== null ) {
				$entities[] = $entity;
			}

			$count++;

			if ( $count >= $this->limit ) {
				break;
			}
		}

		return $entities;
	}

}
