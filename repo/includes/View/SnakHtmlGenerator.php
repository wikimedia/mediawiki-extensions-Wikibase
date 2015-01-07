<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use ValueFormatters\FormattingException;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Template\TemplateFactory;

/**
 * Base class for generating Snak html.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 * @author Pragunbhutani
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
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
	 * @param TemplateFactory $templateFactory
	 * @param SnakFormatter $snakFormatter
	 * @param EntityIdFormatter $propertyIdFormatter
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		SnakFormatter $snakFormatter,
		EntityIdFormatter $propertyIdFormatter
	) {
		if ( $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
				&& $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_WIDGET ) {
			throw new InvalidArgumentException( '$snakFormatter is expected to return text/html, not '
					. $snakFormatter->getFormat() );
		}

		$this->snakFormatter = $snakFormatter;
		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->templateFactory = $templateFactory;
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
			$formattedValue = '&nbsp;';
		}

		$propertyLink = $showPropertyLink ? $this->makePropertyLink( $snak ) : '';

		$html = $this->templateFactory->render( 'wikibase-snakview',
			// Display property link only once for snaks featuring the same property:
			$propertyLink,
			$snakViewCssClass,
			$formattedValue
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
	 * @return string
	 */
	private function getFormattedSnakValue( $snak ) {
		try {
			$formattedSnak = $this->snakFormatter->formatSnak( $snak );
		} catch ( FormattingException $ex ) {
			return $this->getInvalidSnakMessage();
		} catch ( PropertyNotFoundException $ex ) {
			return $this->getPropertyNotFoundMessage();
		} catch ( InvalidArgumentException $ex ) {
			return $this->getInvalidSnakMessage();
		}

		return $formattedSnak;
	}

	/**
	 * @return string
	 */
	private function getInvalidSnakMessage() {
		return wfMessage( 'wikibase-snakformat-invalid-value' )->parse();
	}

	/**
	 * @return string
	 */
	private function getPropertyNotFoundMessage() {
		return wfMessage ( 'wikibase-snakformat-propertynotfound' )->parse();
	}

}
