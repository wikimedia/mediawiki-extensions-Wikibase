<?php

namespace Wikibase;
use ValueFormatters\FormatterOptions;
use DataValues\DataValue;

/**
 * {{#property}} parser function
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

	/* @var Language */
	protected $language;

	/* @var EntityLookup */
	protected $entityLookup;

	/* @var PropertyLookup */
	protected $propertyLookup;

	/* @var ParserErrorMessageFormatter */
	protected $errorFormatter;

	/* @var array */
	protected $availableDataTypes;

	/**
	 * @since 0.4
	 *
	 * @param \Language $language
	 * @param EntityLookup $entityLookup
	 * @param PropertyLookup $propertyLookup
	 * @param ParserErrorMessageFormatter $errorFormatter
	 * @param string $dataTypes[]
	 */
	public function __construct( \Language $language, EntityLookup $entityLookup, PropertyLookup $propertyLookup,
		ParserErrorMessageFormatter $errorFormatter, array $dataTypes ) {
		$this->language = $language;
		$this->entityLookup = $entityLookup;
		$this->propertyLookup = $propertyLookup;
		$this->errorFormatter = $errorFormatter;
		$this->availableDataTypes = $dataTypes;
	}

	/**
	 * Get value of EntityId DataValue
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	protected function getEntityIdValue( EntityId $entityId ) {
		wfProfileIn( __METHOD__ );

		// @todo we could use the terms table to lookup label
		// we would need to have some store lookup code in WikibaseLib
		$entity = $this->entityLookup->getEntity( $entityId );
		$label = $entity->getLabel( $this->language->getCode() );

		// @todo ick! handle when there is no label...
		$labelValue = $label !== false ? $label : '';

		wfProfileOut( __METHOD__ );
		return $labelValue;
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatDataValue( DataValue $dataValue ) {
		wfProfileIn( __METHOD__ );
		$dataType = $dataValue->getType();

		// @fixme why is $dataType inconsistent with data type settings?
		if ( !in_array( $dataType, $this->availableDataTypes ) && $dataType !== 'wikibase-entityid' ) {
			// @todo error handling, data type not supported
			return '';
		}

		$formatterOptions = new FormatterOptions( array( 'lang' => $this->language->getCode() ) );
		$formattedValue = '';

		if ( $dataType === 'wikibase-entityid' ) {
			$valueFormatter = new ItemFormatter( $formatterOptions, $this->entityLookup );
			$formattedValue = $valueFormatter->format( $dataValue );
		} else if ( in_array( $dataType, array( 'commonsMedia', 'string' ) ) ) {
			$valueFormatter = new StringFormatter( $formatterOptions );
			$formattedValue = $valueFormatter->format( $dataValue );
		}

		wfProfileOut( __METHOD__ );
		return $formattedValue;
	}

	/**
	 * @since 0.4
	 *
	 * @param SnakList $snakList
	 *
	 * @return string - wikitext format
	 */
	public function formatSnakList( SnakList $snakList, $propertyLabel ) {
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
	 * @param string $propertyLabel
	 *
	 * @return string - wikitext format
	 */
    public function evaluate( EntityId $entityId, $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		$snakList = new SnakList();

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

		$instance = new self( $targetLanguage, $entityLookup, $propertyLookup,
			$errorFormatter, Settings::get( 'dataTypes' ) );

		$result = array(
			$instance->evaluate( $entityId, $propertyLabel ),
			'noparse' => false
		);

		wfProfileOut( __METHOD__ );
		return $result;
	}

}
