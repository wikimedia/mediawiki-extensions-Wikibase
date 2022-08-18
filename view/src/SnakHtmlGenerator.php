<?php

namespace Wikibase\View;

use InvalidArgumentException;
use ValueFormatters\FormattingException;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\View\Template\TemplateFactory;

/**
 * Base class for generating Snak html.
 *
 * @license GPL-2.0-or-later
 */
class SnakHtmlGenerator {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var EntityIdFormatter
	 */
	private $propertyIdFormatter;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param SnakFormatter $snakFormatter
	 * @param EntityIdFormatter $propertyIdFormatter
	 * @param LocalizedTextProvider $textProvider
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		SnakFormatter $snakFormatter,
		EntityIdFormatter $propertyIdFormatter,
		LocalizedTextProvider $textProvider
	) {
		$validFormats = [ SnakFormatter::FORMAT_HTML, SnakFormatter::FORMAT_HTML_VERBOSE ];
		if ( !in_array( $snakFormatter->getFormat(), $validFormats ) ) {
			throw new InvalidArgumentException(
				'$snakFormatter is expected to return text/html (optionally with disposition=verbose), not ' . $snakFormatter->getFormat()
			);
		}

		$this->snakFormatter = $snakFormatter;
		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->templateFactory = $templateFactory;
		$this->textProvider = $textProvider;
	}

	/**
	 * Generates the HTML for a single snak.
	 *
	 * @param Snak $snak
	 * @param bool $showPropertyLink
	 *
	 * @return string
	 */
	public function getSnakHtml( Snak $snak, $showPropertyLink = false ) {
		$snakViewVariation = $this->getSnakViewVariation( $snak );
		$snakViewCssClass = 'wikibase-snakview-variation-' . $snakViewVariation;

		$formattedValue = $this->getFormattedSnakValue( $snak );

		if ( $formattedValue === '' ) {
			$formattedValue = "\u{00A0}";
		}

		$propertyLink = $showPropertyLink ? $this->makePropertyLink( $snak ) : '';

		$html = $this->templateFactory->render( 'wikibase-snakview',
			// Display property link only once for snaks featuring the same property:
			$propertyLink,
			$snakViewCssClass,
			$formattedValue,
			$snak->getHash()
		);

		return $html;
	}

	/**
	 * @param Snak $snak
	 *
	 * @return string
	 */
	private function makePropertyLink( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		$propertyLink = $this->propertyIdFormatter->formatEntityId( $propertyId );

		return $propertyLink;
	}

	/**
	 * @param Snak $snak
	 *
	 * @return string
	 */
	private function getSnakViewVariation( Snak $snak ) {
		return $snak->getType() . 'snak';
	}

	/**
	 * @fixme handle errors more consistently as done in JS UI, and perhaps add
	 * localised exception messages.
	 *
	 * @param Snak $snak
	 *
	 * @return string HTML
	 */
	private function getFormattedSnakValue( Snak $snak ) {
		try {
			$formattedSnak = $this->snakFormatter->formatSnak( $snak );
		} catch ( FormattingException $ex ) {
			return $this->getInvalidSnakMessage();
		} catch ( PropertyDataTypeLookupException $ex ) {
			return $this->getPropertyNotFoundMessage();
		} catch ( InvalidArgumentException $ex ) {
			return $this->getInvalidSnakMessage();
		}

		return $formattedSnak;
	}

	/**
	 * @return string HTML
	 */
	private function getInvalidSnakMessage() {
		return $this->textProvider->getEscaped( 'wikibase-snakformat-invalid-value' );
	}

	/**
	 * @return string HTML
	 */
	private function getPropertyNotFoundMessage() {
		return $this->textProvider->getEscaped( 'wikibase-snakformat-propertynotfound' );
	}

}
