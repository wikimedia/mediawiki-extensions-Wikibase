<?php

namespace Wikibase;

use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Lib\SnakFormatter;

/**
 * Handler of the {{#property}} parser function.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyParserFunction {

	/**
	 * @var \Parser
	 */
	protected $parser;

	/**
	 * @var EntityId
	 */
	protected $entityId;

	/**
	 * Constructor.
	 *
	 * @param \Parser $parser
	 * @param EntityId $entityId
	 */
	public function __construct( \Parser $parser, EntityId $entityId ) {
		$this->parser = $parser;
		$this->entityId = $entityId;
	}

	/**
	 * Check whether variants are used in this parser run.
	 *
	 * @param \Parser $parser
	 * @return bool
	 */
	public function isParserUsingVariants() {
		$parserOptions = $this->parser->getOptions();
		return $this->parser->OutputType() === \Parser::OT_HTML && !$parserOptions->getInterfaceMessage()
			&& !$parserOptions->getDisableContentConversion();
	}

	/**
	 * Post-process rendered array (variant text) into wikitext to be used in pages.
	 *
	 * @param array $textArray
	 * @return string
	 */
	public function processRenderedArray( $textArray ) {
		// This condition is less strict than self::isParserUsingVariants().
		if ( $this->parser->OutputType() === \Parser::OT_HTML || $this->parser->OutputType() === \Parser::OT_PREPROCESS ) {
			$textArray = array_map( 'wfEscapeWikitext', $textArray );
		}
		// XXX: When "else", we may still want to escape semicolons for -{ }-, but escaping them doesn't really work there...

		// We got arrays, so they must have already checked that variants are being used.
		$text = '-{';
		foreach ( $textArray as $variantCode => $variantText ) {
			$text .= "$variantCode:$variantText;";
		}
		$text .= '}-';

		return $text;
	}

	/**
	 * Post-process rendered text into wikitext to be used in pages.
	 *
	 * @param string $text
	 * @return string
	 */
	public function processRenderedText( $text ) {
		// This condition is less strict than self::isParserUsingVariants().
		if ( $this->parser->OutputType() === \Parser::OT_HTML || $this->parser->OutputType() === \Parser::OT_PREPROCESS ) {
			$text = wfEscapeWikitext( $text );
		}

		return $text;
	}

	/**
	 * Build a PropertyParserFunctionRenderer object for a given language.
	 *
	 * @param \Language $language
	 * @return PropertyParserFunctionRenderer
	 */
	public function getRenderer( \Language $language ) {
		wfProfileIn( __METHOD__ );

		$errorFormatter = new ParserErrorMessageFormatter( $language );

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
		$propertyLabelResolver = $wikibaseClient->getStore()->getPropertyLabelResolver();
		$formatter = $wikibaseClient->newSnakFormatter();

		$instance = new PropertyParserFunctionRenderer( $language,
			$entityLookup, $propertyLabelResolver,
			$errorFormatter, $formatter );

		wfProfileIn( __METHOD__ );
		return $instance;
	}

	/**
	 * @param string $propertyLabel property label or ID (pXXX)
	 * @param \Language $language
	 *
	 * @return string
	 */
	public function renderInLanguage( $propertyLabel, \Language $language ) {

		$renderer = $this->getRenderer( $language );

		$status = $renderer->renderForEntityId( $this->entityId, $propertyLabel );

		if ( !$status->isGood() ) {
			// stuff the error messages into the ParserOutput, so we can render them later somewhere

			$errors = $this->parser->getOutput()->getExtensionData( 'wikibase-property-render-errors' );
			if ( $errors === null ) {
				$errors = array();
			}

			//XXX: if Status sucked less, we'd could get an array of Message objects
			$errors[] = $status->getWikiText();

			$this->parser->getOutput()->setExtensionData( 'wikibase-property-render-errors', $errors );
		}

		return $status->isOK() ? $status->getValue() : '';
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
			$textArray = array();
			foreach ( $this->parser->getConverterLanguage()->getVariants() as $variantCode ) {
				$variantLanguage = \Language::factory( $variantCode );
				$textArray[$variantCode] = $this->renderInLanguage( $propertyLabel, $variantLanguage );
			}
			$text = $this->processRenderedArray( $textArray );
		} else {
			$text = $this->processRenderedText( $this->renderInLanguage( $propertyLabel, $targetLanguage ) );
		}

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * @since 0.4
	 *
	 * @param \Parser &$parser
	 * @param string $propertyLabel property label or ID (pXXX)
	 *
	 * @return array
	 */
	public static function render( \Parser $parser, $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		$siteId = Settings::get( 'siteGlobalID' );

		$siteLinkLookup = WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable();
		$entityId = $siteLinkLookup->getEntityIdForSiteLink( //FIXME: method not in the interface
			new SimpleSiteLink( $siteId, $parser->getTitle()->getFullText() )
		);

		// @todo handle when site link is not there, such as site link / entity has been deleted...
		if ( $entityId === null ) {
			wfProfileOut( __METHOD__ );
			return '';
		}

		$instance = new self( $parser, $entityId );

		$result = array(
			$instance->doRender( $propertyLabel ),
			'noparse' => false,
		);

		wfProfileOut( __METHOD__ );
		return $result;
	}

}
