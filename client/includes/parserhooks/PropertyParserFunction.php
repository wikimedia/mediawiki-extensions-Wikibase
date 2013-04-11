<?php

namespace Wikibase;

use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\SnakFormatter;

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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyParserFunction {

	/* @var \Language */
	protected $language;

	/* @var PropertyLookup */
	protected $propertyLookup;

	/* @var ParserErrorMessageFormatter */
	protected $errorFormatter;

	/* @var SnakFormatter */
	protected $snaksFormatter;

	/**
	 * @since 0.4
	 *
	 * @param \Language $language
	 * @param PropertyLookup $propertyLookup
	 * @param ParserErrorMessageFormatter $errorFormatter
	 * @param SnakFormatter $dataTypeFactory
	 */
	public function __construct( \Language $language, PropertyLookup $propertyLookup,
		ParserErrorMessageFormatter $errorFormatter, SnakFormatter $snaksFormatter ) {
		$this->language = $language;
		$this->propertyLookup = $propertyLookup;
		$this->errorFormatter = $errorFormatter;
		$this->snaksFormatter = $snaksFormatter;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param string $propertyLabel
	 *
	 * @return string - wikitext format
	 */
    public function renderForEntityId( EntityId $entityId, $propertyLabel ) {
		$snakList = $this->getSnaksForProperty( $entityId, $propertyLabel );

		if ( $snakList->isEmpty() ) {
			return '';
		}

		$snaks = array();

		foreach( $snakList as $snak ) {
			$snaks[] = $snak;
		}

		return $this->formatSnakList( $snaks );
	}

	private function getSnaksForProperty( EntityId $entityId, $propertyLabel ) {
		$propertyIdToFind = EntityId::newFromPrefixedId( $propertyLabel );

		if ( $propertyIdToFind === null ) {
			$langCode = $this->language->getCode();
			return $this->propertyLookup->getMainSnaksByPropertyLabel( $entityId, $propertyLabel, $langCode );
		}

		return $this->propertyLookup->getMainSnaksByPropertyId( $entityId, $propertyIdToFind );
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak[] $snaks
	 *
	 * @return string - wikitext format
	 */
	private function formatSnakList( $snaks ) {
		$formattedValues = $this->snaksFormatter->formatSnaks( $snaks );
		return $this->language->commaList( $formattedValues );
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

		$targetLanguage = $parser->getTargetLanguage();
		$errorFormatter = new ParserErrorMessageFormatter( $targetLanguage );

		$wikibaseClient = WikibaseClient::newInstance();

		$propertyLookup = $wikibaseClient->getStore()->getPropertyLookup();
		$formatter = $wikibaseClient->newSnakFormatter();

		$instance = new self( $targetLanguage, $propertyLookup, $errorFormatter, $formatter );

		$result = array(
			$instance->renderForEntityId( $entityId, $propertyLabel ),
			'noparse' => false,
			'nowiki' => true,
		);

		wfProfileOut( __METHOD__ );
		return $result;
	}

}
