<?php

namespace Wikibase\Repo\Localizer;

use DataValues\DataValue;
use Language;
use SiteLookup;
use ValueFormatters\FormattingException;
use ValueFormatters\NumberLocalizer;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Formatters\MediaWikiNumberLocalizer;

/**
 * ValueFormatter for formatting objects that may be encountered in
 * parameters of ValueValidators\Error objects as wikitext.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MessageParameterFormatter implements ValueFormatter {

	/**
	 * @var ValueFormatter
	 */
	private $dataValueFormatter;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @var SiteLookup
	 */
	private $sites;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var NumberLocalizer
	 */
	private $valueLocalizer;

	/**
	 * @param ValueFormatter $dataValueFormatter A formatter for turning DataValues into wikitext.
	 * @param EntityIdFormatter $entityIdFormatter An entity id formatter returning wikitext.
	 * @param SiteLookup $sites
	 * @param Language $language
	 */
	public function __construct(
		ValueFormatter $dataValueFormatter,
		EntityIdFormatter $entityIdFormatter,
		SiteLookup $sites,
		Language $language
	) {
		$this->dataValueFormatter = $dataValueFormatter;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->sites = $sites;
		$this->language = $language;

		$this->valueLocalizer = new MediaWikiNumberLocalizer( $language );
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param mixed $value The value to format
	 *
	 * @return string The formatted value (as wikitext).
	 * @throws FormattingException
	 */
	public function format( $value ) {
		if ( is_int( $value ) || is_float( $value ) ) {
			return $this->valueLocalizer->localizeNumber( $value );
		} elseif ( $value instanceof DataValue ) {
			return $this->dataValueFormatter->format( $value );
		} elseif ( is_object( $value ) ) {
			return $this->formatObject( $value );
		} elseif ( is_array( $value ) ) {
			return $this->formatValueList( $value );
		}

		return wfEscapeWikiText( strval( $value ) );
	}

	/**
	 * @param array $values
	 *
	 * @return string
	 */
	private function formatValueList( array $values ) {
		$formatted = [];

		foreach ( $values as $key => $value ) {
			$formatted[$key] = $this->format( $value );
		}

		//XXX: commaList should really be in the Localizer interface.
		return $this->language->commaList( $formatted );
	}

	/**
	 * @param object $value
	 *
	 * @return string The formatted value (as wikitext).
	 */
	private function formatObject( $value ) {
		if ( $value instanceof EntityId ) {
			return $this->formatEntityId( $value );
		} elseif ( $value instanceof SiteLink ) {
			return $this->formatSiteLink( $value );
		}

		// hope we can interpolate, and just fail if we can't
		return wfEscapeWikiText( strval( $value ) );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string The formatted ID (as a wikitext link).
	 */
	private function formatEntityId( EntityId $entityId ) {
		return $this->entityIdFormatter->formatEntityId( $entityId );
	}

	/**
	 * @param SiteLink $link
	 *
	 * @return string The formatted link (as a wikitext link).
	 */
	private function formatSiteLink( SiteLink $link ) {
		$siteId = $link->getSiteId();
		$page = $link->getPageName();

		$site = $this->sites->getSite( $link->getSiteId() );

		if ( $site ) {
			$url = $site->getPageUrl( $link->getPageName() );
			return "[$url $siteId:$page]";
		} else {
			return "[$siteId:$page]";
		}
	}

}
