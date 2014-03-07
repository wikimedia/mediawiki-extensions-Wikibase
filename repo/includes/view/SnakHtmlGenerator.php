<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\FormattingException;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\Lib\SnakFormatter;

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
	 * @since 0.4
	 *
	 * @var SnakFormatter
	 */
	protected $snakFormatter;

	/**
	 * @since 0.5
	 *
	 * @var EntityIdHtmlLinkFormatter
	 */
	protected $entityIdHtmlLinkFormatter;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param EntityIdHtmlLinkFormatter $entityIdHtmlLinkFormatter
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		EntityIdHtmlLinkFormatter $entityIdHtmlLinkFormatter
	) {
		$this->snakFormatter = $snakFormatter;
		$this->entityIdHtmlLinkFormatter = $entityIdHtmlLinkFormatter;
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
		$propertyLink = '';
		$snakViewCssClass = 'wb-snakview-variation-' . $this->getSnakViewVariation( $snak );
		$formattedValue = $this->getFormattedSnakValue( $snak );

		if ( $showPropertyLink ) {
			$propertyLink = $this->entityIdHtmlLinkFormatter->formatEntityId(
				$snak->getPropertyId()
			);
		}

		if ( $formattedValue === '' ) {
			$formattedValue = '&nbsp;';
		}

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
			// TODO: Pass info about the entities used in the snak
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
