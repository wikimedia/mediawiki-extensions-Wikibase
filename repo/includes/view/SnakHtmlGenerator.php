<?php

namespace Wikibase\View;

use Html;
use Language;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\EntityTitleLookup;
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
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		EntityTitleLookup $entityTitleLookup,
		Language $language
	) {
		$this->snakFormatter = $snakFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->language = $language;
	}

	/**
	 * Generates the HTML for a single snak.
	 *
	 * @param Snak $snak
	 * @param string[] $propertyLabels
	 * @param boolean $showPropertyLink
	 *
	 * @return string
	 */
	public function getSnakHtml( Snak $snak, array $propertyLabels, $showPropertyLink = false ) {
		$snakViewVariation = $this->getSnakViewVariation( $snak );
		$snakViewCssClass = 'wb-snakview-variation-' . $snakViewVariation;

		try {
			$formattedValue = $this->snakFormatter->formatSnak( $snak );
		} catch ( \Exception $ex ) {
			if ( $ex instanceof MismatchingDataValueTypeException ) {
				$snakViewCssClass .= '-datavaluetypemismatch';
				$formattedValue = $this->getMismatchingDataValueTypeExceptionMessage( $ex );
			} else {
				$snakViewCssClass .= '-formaterror';
				$formattedValue = $this->formatExceptionError( $ex );
			}
		}

		if ( $formattedValue === '' ) {
			$formattedValue = '&nbsp;';
		}

		$propertyLink = $showPropertyLink ?
			$this->makePropertyLink( $snak, $propertyLabels, $showPropertyLink ) : '';

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
	 * @param string[] $propertyLabels
	 *
	 * @return string
	 */
	private function makePropertyLink( Snak $snak, array $propertyLabels ) {
		$propertyId = $snak->getPropertyId();
		$propertyKey = $propertyId->getSerialization();
		$propertyLabel = isset( $propertyLabels[$propertyKey] )
			? $propertyLabels[$propertyKey]
			: $propertyKey;

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
	 * @fixme handle errors more consistently as done in JS UI, and perhaps add
	 * localised exception messages.
	 *
	 * @param \Exception $ex
	 *
	 * @return string
	 */
	protected function formatExceptionError( \Exception $ex ) {
		if ( $ex instanceof Wikibase\Lib\PropertyNotFoundException ) {
			return $this->getPropertyNotFoundMessage();
		} else {
			return $this->getInvalidSnakMessage();
		}
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

	/**
	 * @param MismatchingDataValueTypeException
	 *
	 * @return string
	 */
	private function getMismatchingDataValueTypeExceptionMessage( MismatchingDataValueTypeException $ex ) {
		$details = wfMessage( 'wikibase-snakview-variation-datavaluetypemismatch-details' )
			->params( $ex->getDataValueType(), $ex->getExpectedValueType() )
			->inLanguage( $this->language )
			->parse();

		$errorMessage = wfMessage( 'wikibase-snakview-variation-datavaluetypemismatch' )
			->inLanguage( $this->language )
			->parse();

		$formattedDetailsError = Html::element(
			'div',
			array(
				'class' => 'wb-snakview-variation-valuesnak-datavaluetypemismatch-message'
			),
			$details
		);

		return $errorMessage . $formattedDetailsError;
	}

}
