<?php

namespace Wikibase\i18n;

use DataValues\DataValue;
use Language;
use SiteStore;
use ValueFormatters\FormattingException;
use ValueFormatters\Localizer;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\MediaWikiNumberLocalizer;

/**
 * ValueFormatter for formatting objects that may be encountered in
 * parameters of ValueValidators\Error objects.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MessageParameterFormatter implements ValueFormatter {

	/**
	 * @var Localizer
	 */
	protected $valueLocalizer;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var ValueFormatter
	 */
	protected $dataValueFormatter;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var SiteStore
	 */
	protected $sites;

	function __construct(
		ValueFormatter $dataValueFormatter,
		EntityTitleLookup $entityTitleLookup,
		Language $language,
		SiteStore $sites
	) {
		$this->dataValueFormatter = $dataValueFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->language = $language;
		$this->sites = $sites;
		$this->valueLocalizer = new MediaWikiNumberLocalizer( $language  );
	}

	/**
	 * Formats a value.
	 *
	 * @since 0.1
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
			$list = $this->formatValueList( $value );

			return $this->language->commaList( $list );
		}

		return "$value";
	}

	/**
	 * @param array $values
	 *
	 * @return string[]
	 */
	protected function formatValueList( $values ) {
		$formatted = array();

		foreach ( $values as $k => $value ) {
			$formatted[$k] = $this->format( $value );
		}

		return $formatted;
	}

	/**
	 * @param object $value
	 *
	 * @return string The formatted value (as wikitext).
	 */
	protected function formatObject( $value ) {
		if ( $value instanceof EntityId ) {
			return $this->formatEntityId( $value );
		} elseif ( $value instanceof SiteLink ) {
			return $this->formatSiteLink( $value );
		}

		// hope we can interpolate, and just fail if we can't
		return "$value";
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string The formatted ID (as a wikitext link).
	 */
	private function formatEntityId( EntityId $entityId ) {
		$title = $this->entityTitleLookup->getTitleForId( $entityId );
		$target = $title->getFullText();
		$text = $title->getFullText();

		return "[[$target|$text]]";
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
		$url = $site->getPageUrl( $link->getPageName() );

		return "[$url $siteId:$page]";
	}
}
