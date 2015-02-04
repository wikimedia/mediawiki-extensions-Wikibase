<?php

namespace Wikibase\Repo\Diff;

use Exception;
use InvalidArgumentException;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;

/**
 * Visualizes Snaks for difference views
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class DifferencesSnakVisualizer {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var ValueFormatter
	 */
	private $propertyIdFormatter;

	/**
	 * @var SnakFormatter
	 */
	private $snakBreadCrumbFormatter;

	/**
	 * @var SnakFormatter
	 */
	private $snakDetailsFormatter;

	/**
	 * @param ValueFormatter $propertyIdFormatter Formatter for IDs, must generate HTML.
	 * @param SnakFormatter $snakDetailsFormatter detailed Formatter for Snaks, must generate HTML.
	 * @param SnakFormatter $snakBreadCrumbFormatter terse Formatter for Snaks, must generate HTML.
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ValueFormatter $propertyIdFormatter,
		SnakFormatter $snakDetailsFormatter,
		SnakFormatter $snakBreadCrumbFormatter,
		$languageCode
	) {
		if ( $snakDetailsFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
			&& $snakDetailsFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_DIFF
		) {
			throw new InvalidArgumentException(
				'Expected $snakDetailsFormatter to generate html, not '
				. $snakDetailsFormatter->getFormat() );
		}

		if ( $snakBreadCrumbFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
			&& $snakBreadCrumbFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_DIFF
		) {
			throw new InvalidArgumentException(
				'Expected $snakBreadCrumbFormatter to generate html, not '
				. $snakBreadCrumbFormatter->getFormat() );
		}

		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->snakDetailsFormatter = $snakDetailsFormatter;
		$this->snakBreadCrumbFormatter = $snakBreadCrumbFormatter;
		$this->languageCode = $languageCode;
	}

		/**
	 * @param Snak|null $snak
	 *
	 * @return string HTML
	 */
	public function formatSnakDetails( Snak $snak = null ) {
		if ( $snak === null ) {
			return null;
		}

		try {
			return $this->snakDetailsFormatter->formatSnak( $snak );
		} catch ( Exception $ex ) {
			// @fixme maybe there is a way we can render something more useful
			// we are getting multiple types of exceptions and should handle
			// consistent (and shared code) with what we do in SnakHtmlGenerator.
			$messageText = wfMessage( 'wikibase-snakformat-invalid-value' )
				->inLanguage( $this->languageCode )
				->parse();

			return $messageText;
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	private function formatPropertyId( EntityId $entityId ) {
		try {
			return $this->propertyIdFormatter->format( $entityId );
		} catch ( FormattingException $ex ) {
			return $entityId->getSerialization(); // XXX: or include the error message?
		}
	}

	/**
	 * Get formatted header for a snak, including the snak's property label, but not the snak's value.
	 *
	 * @since 0.4
	 *
	 * @param Snak|null $snak
	 *
	 * @return string HTML
	 */
	public function getSnakLabelHeader( Snak $snak = null ) {
		$headerText = wfMessage( 'wikibase-entity-property' )->inLanguage( $this->languageCode )->parse();

		if ( $snak !== null ) {
			$propertyId = $snak->getPropertyId();
			$headerText .= ' / ' . $this->formatPropertyId( $propertyId );
		}

		return $headerText;
	}

	/**
	 * Get formatted header for a snak, including the snak's property label and value.
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string HTML
	 */
	public function getSnakValueHeader( Snak $snak ) {
		$before = $this->getSnakLabelHeader( $snak );

		try {
			$after = $this->snakBreadCrumbFormatter->formatSnak( $snak );
			$result = $this->getColonSeparatedHtml( $before, $after );
		} catch ( Exception $ex ) {
			// just ignore it
			$result = $before;
		}

		return $result;
	}

	/**
	 * Get a detailed formatted snak, including the snak's property label and value.
	 * 
	 * @param Snak $snak
	 */
	public function formatSnak( Snak $snak ) {
		return $this->getColonSeparatedHtml(
			$this->formatPropertyId( $snak->getPropertyId() ),
			$this->formatSnakDetails( $snak )
		);
	}

	private function getColonSeparatedHtml( $before, $after ) {
		$colon = wfMessage( 'colon-separator' )->inLanguage( $this->languageCode )->escaped();

		return $before . $colon . $after;
	}

}
