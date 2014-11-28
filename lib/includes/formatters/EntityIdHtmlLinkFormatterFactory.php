<?php
namespace Wikibase\Lib;

use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;

/**
 * A factory interface for generating EntityIdFormatters.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactory implements EntityIdFormatterFactory {

	/**
	 * @var LabelLookup
	 */
	private $labelLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @param LabelLookup $labelLookup
	 * @param EntityTitleLookup $titleLookup
	 */
	public function __construct( LabelLookup $labelLookup, EntityTitleLookup $titleLookup ) {
		$this->labelLookup = $labelLookup;
		$this->titleLookup = $titleLookup;
	}

	/**
	 * @see EntityIdFormatterFactory::getOutputFormat
	 *
	 * @return string SnakFormatter::FORMAT_HTML
	 */
	public function getOutputFormat() {
		return SnakFormatter::FORMAT_HTML;
	}

	/**
	 * @see EntityIdFormatterFactory::getEntityIdFormater
	 *
	 * @param FormatterOptions $options
	 *
	 * @return EntityIdHtmlLinkFormatter
	 */
	public function getEntityIdFormater( FormatterOptions $options ) {
		return new EntityIdHtmlLinkFormatter( $options, $this->labelLookup, $this->titleLookup );
	}

}
