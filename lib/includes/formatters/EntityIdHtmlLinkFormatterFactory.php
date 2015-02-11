<?php
namespace Wikibase\Lib;

use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * A factory for generating EntityIdHtmlLinkFormatters.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactory implements EntityIdFormatterFactory {

	/**
	 * @var FormatterLabelLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @param FormatterLabelLookupFactory $labelLookupFactory
	 * @param EntityTitleLookup $titleLookup
	 */
	public function __construct(
		FormatterLabelLookupFactory $labelLookupFactory,
		EntityTitleLookup $titleLookup,
		ContentLanguages $termsLanguages
	) {
		$this->labelLookupFactory = $labelLookupFactory;
		$this->termsLanguages = $termsLanguages;
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
		$labelLookup = $this->labelLookupFactory->getLabelLookup( $options );
		return new EntityIdHtmlLinkFormatter(
			$options,
			$labelLookup,
			$this->titleLookup,
			$this->termsLanguages
		);
	}

}
