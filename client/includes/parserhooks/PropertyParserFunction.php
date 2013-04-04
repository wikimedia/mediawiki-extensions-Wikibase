<?php

namespace Wikibase;

use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use ValueFormatters\ValueFormatter;
use Wikibase\Client\WikibaseClient;

/**
 * Handler of the {{#property}} parser function.
 *
 * TODO: cleanup injection of dependencies
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
 */
class PropertyParserFunction {

	/* @var \Language */
	protected $language;

	/* @var EntityLookup */
	protected $entityLookup;

	/* @var PropertyLookup */
	protected $propertyLookup;

	/* @var ParserErrorMessageFormatter */
	protected $errorFormatter;

	/* @var DataTypeFactory */
	protected $dataTypeFactory;

	/**
	 * @since 0.4
	 *
	 * @param \Language $language
	 * @param EntityLookup $entityLookup
	 * @param PropertyLookup $propertyLookup
	 * @param ParserErrorMessageFormatter $errorFormatter
	 * @param string[] $dataTypeFactory
	 */
	public function __construct( \Language $language, EntityLookup $entityLookup, PropertyLookup $propertyLookup,
		ParserErrorMessageFormatter $errorFormatter, DataTypeFactory $dataTypeFactory ) {
		$this->language = $language;
		$this->entityLookup = $entityLookup;
		$this->propertyLookup = $propertyLookup;
		$this->errorFormatter = $errorFormatter;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * Format a data value
	 *
	 * TODO: this method should go into its own class
	 *
	 * @since 0.4
	 *
	 * @param DataValue $dataValue
	 *
	 * @return string
	 * @throws \RuntimeException
	 */
	private function formatDataValue( DataValue $dataValue ) {
		$dataType = $this->dataTypeFactory->getType( $dataValue->getType() );

		if ( $dataType === null ) {
			throw new \RuntimeException( "Value of unsupported datatype '$dataType' cannot be formatted" );
		}

		// TODO: update this code to obtain the string formatter as soon as corresponding changes
		// in the DataTypes library have been made.
		$valueFormatters = $dataType->getFormatters();
		$valueFormatter = reset( $valueFormatters );

		if ( $valueFormatter === false ) {
			$value = $dataValue->getValue();

			if ( is_string( $value ) ) {
				wfProfileOut( __METHOD__ );
				return $value;
			}

			wfProfileOut( __METHOD__ );

			// TODO: implement: error message or other error handling
			return 'ERROR: TODO';
		}

		/**
		 * @var ValueFormatter $valueFormatter
		 */
		$formattingResult = $valueFormatter->format( $dataValue );

		if ( $formattingResult->isValid() ) {
			wfProfileOut( __METHOD__ );
			return $formattingResult->getValue();
		}


		wfProfileOut( __METHOD__ );

		// TODO: implement: error message or other error handling
		return 'ERROR: TODO';
	}

	/**
	 * Formats data values in a SnakList as comma separated list
	 * @todo this belongs elsewhere, such as with formatters
	 *
	 * @since 0.4
	 *
	 * @param SnakList $snakList
	 * @param string $propertyLabel
	 *
	 * @return string - wikitext format
	 */
	private function formatSnakList( SnakList $snakList, $propertyLabel ) {
		wfProfileIn( __METHOD__ );
		$values = array();

		foreach( $snakList as $snak ) {
			// @todo handle other snak types
			if ( $snak instanceof PropertyValueSnak ) {
				$values[] = $snak->getDataValue();
			}
		}

		if ( $values !== array() ) {
			wfProfileOut( __METHOD__ );
			$formattedValues = array();

			foreach( $values as $dataValue ) {
				$formattedValues[] = $this->formatDataValue( $dataValue );
			}

			return $this->language->commaList( $formattedValues );
		}

		if ( ! isset( $errorMessage ) ) {
			// formatted as empty string
			$errorMessage = new \Message( 'wikibase-property-notfound', array( wfEscapeWikiText( $propertyLabel ) ) );
		}

		wfProfileOut( __METHOD__ );
		return $this->errorFormatter->format( $errorMessage );
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param string $propertyLabel
	 *
	 * @return string - wikitext format
	 */
    public function evaluate( EntityId $entityId, $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		$propertyIdToFind = EntityId::newFromPrefixedId( $propertyLabel );

		if ( $propertyIdToFind !== null ) {
			$snakList = $this->propertyLookup->getMainSnaksByPropertyId( $entityId, $propertyIdToFind );
		} else {
			$langCode = $this->language->getCode();
			$snakList = $this->propertyLookup->getMainSnaksByPropertyLabel( $entityId, $propertyLabel, $langCode );
		}

		if ( $snakList->isEmpty() ) {
			wfProfileOut( __METHOD__ );
			return '';
		}

		wfProfileOut( __METHOD__ );
		return $this->formatSnakList( $snakList, $propertyLabel );
	}

	/**
	 * @since 0.4
	 *
	 * @param \Parser &$parser
	 *
	 * @return string
	 */
	public static function render( \Parser $parser, $propertyLabel ) {
		wfProfileIn( __METHOD__ );
		$site = \Sites::singleton()->getSite( Settings::get( 'siteGlobalID' ) );

		$siteLinkLookup = ClientStoreFactory::getStore()->newSiteLinkTable();
		$entityId = $siteLinkLookup->getEntityIdForSiteLink(
			new SiteLink( $site, $parser->getTitle()->getFullText() )
		);

		// @todo handle when site link is not there, such as site link / entity has been deleted...
		if ( $entityId === null ) {
			wfProfileOut( __METHOD__ );
			return '';
		}

		$entityLookup = ClientStoreFactory::getStore()->getEntityLookup();

		$targetLanguage = $parser->getTargetLanguage();
		$errorFormatter = new ParserErrorMessageFormatter( $targetLanguage );

		// returns lookup with full label and id lookup (experimental) or just id lookup
		$propertyLookup = ClientStoreFactory::getStore()->getPropertyLookup();

		$dataTypeFactory = WikibaseClient::newInstance()->getDataTypeFactory();

		$instance = new self( $targetLanguage, $entityLookup, $propertyLookup, $errorFormatter, $dataTypeFactory );

		$result = array(
			$instance->evaluate( $entityId, $propertyLabel ),
			'noparse' => false
		);

		wfProfileOut( __METHOD__ );
		return $result;
	}

}
