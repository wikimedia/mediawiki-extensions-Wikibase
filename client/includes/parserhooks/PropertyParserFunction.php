<?php

namespace Wikibase;

use InvalidArgumentException;
use Language;
use Parser;
use Status;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Handler of the {{#property}} parser function.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class PropertyParserFunction {

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver;

	/**
	 * @param Parser $parser
	 * @param EntityId $entityId
	 * @param EntityLookup $entityLookup
	 * @param PropertyLabelResolver $propertyLabelResolver
	 */
	public function __construct( Parser $parser, EntityId $entityId,
		EntityLookup $entityLookup, PropertyLabelResolver $propertyLabelResolver
	) {
		$this->parser = $parser;
		$this->entityId = $entityId;
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
	}

	/**
	 * Check whether variants are used in this parser run.
	 *
	 * @return bool
	 */
	public function isParserUsingVariants() {
		$parserOptions = $this->parser->getOptions();
		return $this->parser->OutputType() === Parser::OT_HTML && !$parserOptions->getInterfaceMessage()
			&& !$parserOptions->getDisableContentConversion();
	}

	/**
	 * Post-process rendered array (variant text) into wikitext to be used in pages.
	 *
	 * @param string[] $textArray
	 *
	 * @return string
	 */
	public function processRenderedArray( array $textArray ) {
		// We got arrays, so they must have already checked that variants are being used.
		$text = '';
		foreach ( $textArray as $variantCode => $variantText ) {
			$text .= "$variantCode:$variantText;";
		}
		if ( $text !== '' ) {
			$text = '-{' . $text . '}-';
		}

		return $text;
	}

	/**
	 * Build a PropertyParserFunctionRenderer object for a given language.
	 *
	 * @param Language $language
	 *
	 * @return PropertyParserFunctionRenderer
	 */
	public function getRenderer( Language $language ) {
		wfProfileIn( __METHOD__ );

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$languageFallbackChainFactory = $wikibaseClient->getLanguageFallbackChainFactory();
		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $language,
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		);

		$options = new FormatterOptions( array(
			'languages' => $languageFallbackChain,
			// ...more options...
		) );

		$snaksFormatter = $wikibaseClient->newSnakFormatter( SnakFormatter::FORMAT_WIKI, $options );

		$instance = new PropertyParserFunctionRenderer( $language,
			$this->entityLookup, $this->propertyLabelResolver,
			$snaksFormatter );

		wfProfileOut( __METHOD__ );
		return $instance;
	}

	/**
	 * @param string $propertyLabel property label or ID (pXXX)
	 * @param Language $language
	 *
	 * @return string
	 */
	public function renderInLanguage( $propertyLabel, Language $language ) {
		$renderer = $this->getRenderer( $language );

		try {
			$status = $renderer->renderForEntityId( $this->entityId, $propertyLabel );
		} catch ( PropertyLabelNotResolvedException $ex ) {
			$status = $this->getStatusForException( $propertyLabel, $ex->getMessage() );
		} catch ( InvalidArgumentException $ex ) {
			$status = $this->getStatusForException( $propertyLabel, $ex->getMessage() );
		}

		if ( !$status->isGood() ) {
			$error = $status->getMessage()->inLanguage( $language )->text();
			return '<p class="error wikibase-error">' . $error . '</p>';
		}

		return $status->getValue();
	}

	/**
	 * @param string $propertyLabel
	 * @param string $message
	 *
	 * @return Status
	 */
	private function getStatusForException( $propertyLabel, $message ) {
		return Status::newFatal(
			'wikibase-property-render-error',
			$propertyLabel,
			$message
		);
	}

	/**
	 * @param string $propertyLabel property label or ID (pXXX)
	 * @param string[] $variants Variant codes
	 *
	 * @return string[], key by variant codes
	 */
	public function renderInVariants( $propertyLabel, array $variants ) {
		$textArray = array();

		foreach ( $variants as $variantCode ) {
			$variantLanguage = Language::factory( $variantCode );
			$variantText = $this->renderInLanguage( $propertyLabel, $variantLanguage );
			// LanguageConverter doesn't handle empty strings correctly, and it's more difficult
			// to fix the issue there, as it's using empty string as a special value.
			// Also keeping the ability to check a missing property with {{#if: }} is another reason.
			if ( $variantText !== '' ) {
				$textArray[$variantCode] = $variantText;
			}
		}

		return $textArray;
	}

	/**
	 * @param string $propertyLabel property label or ID (pXXX)
	 *
	 * @return string Wikitext
	 */
	public function doRender( $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		$targetLanguage = $this->parser->getTargetLanguage();

		if ( $this->isParserUsingVariants() && $this->parser->getConverterLanguage()->hasVariants() ) {
			$text = $this->processRenderedArray( $this->renderInVariants(
				$propertyLabel, $this->parser->getConverterLanguage()->getVariants()
			) );
		} else {
			$text = $this->renderInLanguage( $propertyLabel, $targetLanguage );
		}

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * @since 0.4
	 *
	 * @param Parser &$parser
	 * @param string $propertyLabel property label or ID (pXXX)
	 *
	 * @return array
	 */
	public static function render( Parser $parser, $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$siteId = $wikibaseClient->getSettings()->getSetting( 'siteGlobalID' );

		$siteLinkLookup = $wikibaseClient->getStore()->getSiteLinkTable();
		$entityId = $siteLinkLookup->getEntityIdForSiteLink( //FIXME: method not in the interface
			new SimpleSiteLink( $siteId, $parser->getTitle()->getFullText() )
		);

		// @todo handle when site link is not there, such as site link / entity has been deleted...
		if ( $entityId === null ) {
			wfProfileOut( __METHOD__ );
			return '';
		}

		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
		$propertyLabelResolver = $wikibaseClient->getStore()->getPropertyLabelResolver();

		$instance = new self( $parser, $entityId, $entityLookup, $propertyLabelResolver );

		$result = array(
			$instance->doRender( $propertyLabel ),
			'noparse' => false, // parse wikitext
			'nowiki' => false,  // formatters take care of escaping as needed
		);

		wfProfileOut( __METHOD__ );
		return $result;
	}

}
