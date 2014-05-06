<?php

namespace Wikibase\View;

use InvalidArgumentException;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\EntityTitleLookup;
use Wikibase\i18n\ExceptionLocalizer;
use Wikibase\Lib\FormattingException;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\Lib\MismatchingSnakValueFormatter;
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
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var ExceptionLocalizer
	 */
	protected $exceptionLocalizer;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param ExceptionLocalizer $exceptionLoc
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		EntityTitleLookup $entityTitleLookup,
		ExceptionLocalizer $exceptionLocalizer
	) {
		$this->snakFormatter = $snakFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
	}

	/**
	 * Generates the HTML for a single snak.
	 *
	 * @param Snak $snak
	 * @param array[] $entityInfo
	 * @param bool $showPropertyLink
	 *
	 * @return string
	 */
	public function getSnakHtml( Snak $snak, array $entityInfo, $showPropertyLink = false ) {
		$snakViewVariation = $this->getSnakViewVariation( $snak );
		$snakViewCssClass = 'wb-snakview-variation-' . $snakViewVariation;

		try {
			$formattedValue = $this->snakFormatter->formatSnak( $snak );
		} catch ( \Exception $ex ) {
			if ( $ex instanceof MismatchingDataValueTypeException ) {
				$snakViewCssClass .= '-datavaluetypemismatch';
				$formattedValue = $this->formatDataValueMismatchError( $ex );
			} else {
				$snakViewCssClass .= '-formaterror';
				$formattedValue = $this->formatExceptionError( $ex );
			}
		}

		if ( $formattedValue === '' ) {
			$formattedValue = '&nbsp;';
		}

		$propertyLink = $showPropertyLink ?
			$this->makePropertyLink( $snak, $entityInfo, $showPropertyLink ) : '';

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
	 * @param array[] $entityInfo
	 *
	 * @return string
	 */
	private function makePropertyLink( Snak $snak, array $entityInfo ) {
		$propertyId = $snak->getPropertyId();
		$key = $propertyId->getSerialization();
		$propertyLabel = $key;
		if ( isset( $entityInfo[$key] ) && !empty( $entityInfo[$key]['labels'] ) ) {
			$entityInfoLabel = reset( $entityInfo[$key]['labels'] );
			$propertyLabel = $entityInfoLabel['value'];
		}

		// @todo use EntityIdHtmlLinkFormatter here
		$propertyLink = \Linker::link(
			$this->entityTitleLookup->getTitleForId( $propertyId ),
			htmlspecialchars( $propertyLabel )
		);

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
	 * @param MismatchingDataValueTypeException $ex
	 *
	 * @return string
	 */
	protected function formatDataValueMismatchError( MismatchingDataValueTypeException $ex ) {
		$mismatchingSnakValueFormatter = new MismatchingSnakValueFormatter(
				SnakFormatter::FORMAT_HTML
			);

		return $mismatchingSnakValueFormatter->format(
			$ex->getExpectedValueType(),
			$ex->getDataValueType()
		);
	}

	/**
	 * @param \Exception $ex
	 *
	 * @return string
	 */
	protected function formatExceptionError( \Exception $ex ) {
		$message = $this->exceptionLocalizer->getMessage( $ex );
		return $message->parse();
	}

}
