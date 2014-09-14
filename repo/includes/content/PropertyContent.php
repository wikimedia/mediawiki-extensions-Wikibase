<?php

namespace Wikibase;

use Content;
use Language;
use ParserOutput;
use Wikibase\Repo\ParserOutput\StatementsParserOutputGenerator;
use Wikibase\Repo\View\ClaimsViewFactory;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * Content object for articles representing Wikibase properties.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContent extends EntityContent {

	/**
	 * @var Property
	 */
	private $property;

	/**
	 * Do not use to construct new stuff from outside of this class,
	 * use the static newFoobar methods.
	 *
	 * In other words: treat as protected (which it was, but now
	 * cannot be since we derive from Content).
	 *
	 * @protected
	 *
	 * @param Property $property
	 */
	public function __construct( Property $property ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY );
		$this->property = $property;
	}

	/**
	 * Create a new propertyContent object for the provided property.
	 *
	 * @param Property $property
	 *
	 * @return PropertyContent
	 */
	public static function newFromProperty( Property $property ) {
		return new static( $property );
	}

	/**
	 * Gets the property that makes up this property content.
	 *
	 * @return Property
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * Sets the property that makes up this property content.
	 *
	 * @param Property $property
	 */
	public function setProperty( Property $property ) {
		$this->property = $property;
	}

	/**
	 * Returns a new empty PropertyContent.
	 *
	 * @return PropertyContent
	 */
	public static function newEmpty() {
		return new static( Property::newFromType( 'string' ) );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @return Property
	 */
	public function getEntity() {
		return $this->property;
	}

	/**
	 * Checks if this PropertyContent is valid for saving.
	 *
	 * Returns false if the entity does not have a DataType set.
	 *
	 * @see Content::isValid()
	 */
	public function isValid() {
		if ( !parent::isValid() ) {
			return false;
		}

		if ( is_null( $this->getEntity()->getDataTypeId() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @see getEntityView
	 *
	 * @param Language $language
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @return PropertyView
	 */
	protected function getEntityView( Language $language, LanguageFallbackChain $languageFallbackChain ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$sectionEditLinkGenerator = new SectionEditLinkGenerator();

		$fingerprintView = new FingerprintView(
			$sectionEditLinkGenerator,
			$language->getCode()
		);

		$claimsViewFactory = new ClaimsViewFactory(
			$wikibaseRepo->getSnakFormatterFactory(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getStore()->getEntityInfoBuilderFactory()
		);

		$claimsView = $claimsViewFactory->createClaimsView( $language->getCode(), $languageFallbackChain );

		return new PropertyView(
			$fingerprintView,
			$claimsView,
			$wikibaseRepo->getDataTypeFactory(),
			$language
		);
	}

	/**
	 * @see EntityContent::addDataToParserOutput
	 *
	 * @param ParserOutput $pout
	 */
	protected function addDataToParserOutput( ParserOutput $pout ) {
		parent::addDataToParserOutput( $pout );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$statementsParserOutputGenerator = new StatementsParserOutputGenerator(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getPropertyDataTypeLookup()
		);

		$statementsParserOutputGenerator->assignToParserOutput( $pout, $this->getProperty()->getStatements() );
	}

}
