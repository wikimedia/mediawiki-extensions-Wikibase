<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use ValueFormatters\FormattingException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Base class for generating Snak html.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 * @author Pragunbhutani
 * @author Katie Filbert < aude.wiki@gmail.com>
 */
class SnakHtmlGenerator {

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @since 0.4
	 *
	 * @var SnakFormatter
	 */
	protected $snakFormatter;

	/**
	 * @since 0.5
	 *
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @param EntityIdFormatter $entityIdFormatter
	 * @param SnakFormatter $snakFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdFormatter $entityIdFormatter,
		SnakFormatter $snakFormatter,
		EntityTitleLookup $entityTitleLookup
	) {
		if ( $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
				&& $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_WIDGET ) {
			throw new InvalidArgumentException( '$snakFormatter is expected to return text/html, not '
					. $snakFormatter->getFormat() );
		}

		$this->entityIdFormatter = $entityIdFormatter;
		$this->snakFormatter = $snakFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
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
		$snakViewCssClass = 'wb-snakview-variation-' . $snakViewVariation;

		$formattedValue = $this->getFormattedSnakValue( $snak );

		if ( $formattedValue === '' ) {
			$formattedValue = '&nbsp;';
		}

		$propertyLink = $showPropertyLink ?
			$this->makePropertyLink( $snak, $showPropertyLink ) : '';

		$html = wfTemplate( 'wb-snak',
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

		return $this->entityIdFormatter->format( $propertyId );
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
	protected function getFormattedSnakValue( $snak ) {
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
