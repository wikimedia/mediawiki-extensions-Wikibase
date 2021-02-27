<?php

namespace Wikibase\Lib\Formatters\Reference;

use DataValues\StringValue;
use MessageLocalizer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @inheritDoc
 * @license GPL-2.0-or-later
 */
class DataBridgeReferenceFormatter implements ReferenceFormatter {

	/** @var SnakFormatter */
	private $snakFormatter;

	/** @var WellKnownReferenceProperties */
	private $properties;

	/** @var MessageLocalizer */
	private $messageLocalizer;

	/**
	 * @param SnakFormatter $snakFormatter should generate SnakFormatter::FORMAT_WIKI
	 * @param WellKnownReferenceProperties $properties
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		WellKnownReferenceProperties $properties,
		MessageLocalizer $messageLocalizer
	) {
		$this->snakFormatter = $snakFormatter;
		$this->properties = $properties;
		$this->messageLocalizer = $messageLocalizer;
	}

	/** @inheritDoc */
	public function formatReference( Reference $reference ): string {
		$referenceSnaks = new ByCertainPropertyIdGrouper( $reference->getSnaks(), [
			$this->properties->referenceUrlPropertyId,
			$this->properties->titlePropertyId,
			$this->properties->statedInPropertyId,
			$this->properties->authorPropertyId,
			$this->properties->publisherPropertyId,
			$this->properties->publicationDatePropertyId,
			$this->properties->retrievedDatePropertyId,
		] );

		$formattedParts = [];
		$separator = $this->messageLocalizer->msg( 'wikibase-reference-formatter-snak-separator' )->text();
		$terminator = $this->messageLocalizer->msg( 'wikibase-reference-formatter-snak-terminator' )->text();

		$referenceUrlSnaks = $referenceSnaks->getByPropertyId( $this->properties->referenceUrlPropertyId );
		$titleSnaks = $referenceSnaks->getByPropertyId( $this->properties->titlePropertyId );
		$referenceLink = $this->formatReferenceLink( $referenceUrlSnaks, $titleSnaks );
		if ( $referenceLink !== null ) {
			$formattedParts[] = $referenceLink;
			$referenceUrlSnaks = [];
			$titleSnaks = [];
		}

		foreach ( [
			$referenceUrlSnaks,
			$titleSnaks,
			$referenceSnaks->getByPropertyId( $this->properties->statedInPropertyId ),
			$referenceSnaks->getByPropertyId( $this->properties->authorPropertyId ),
			$referenceSnaks->getByPropertyId( $this->properties->publisherPropertyId ),
			$referenceSnaks->getByPropertyId( $this->properties->publicationDatePropertyId ),
			$referenceSnaks->getOthers(),
		] as $snaks ) {
			foreach ( $snaks as $snak ) {
				$formattedParts[] = $this->snakFormatter->formatSnak( $snak );
			}
		}
		foreach ( $referenceSnaks->getByPropertyId( $this->properties->retrievedDatePropertyId ) as $snak ) {
			$formattedParts[] = $this->messageLocalizer->msg(
				'wikibase-reference-formatter-snak-retrieved',
				$this->snakFormatter->formatSnak( $snak )
			);
		}

		$output = implode( $separator, $formattedParts );
		if ( $output !== '' ) {
			$output .= $terminator;
		}
		return $output;
	}

	/** Format reference URL(s) and title(s) into exactly one link, if possible. */
	private function formatReferenceLink( array $referenceUrlSnaks, array $titleSnaks ): ?string {
		if ( count( $referenceUrlSnaks ) === 1 && count( $titleSnaks ) === 1 ) {
			$referenceUrlSnak = $referenceUrlSnaks[0];
			$titleSnak = $titleSnaks[0];
			if ( $referenceUrlSnak instanceof PropertyValueSnak && $titleSnak instanceof PropertyValueSnak ) {
				$referenceUrl = $referenceUrlSnak->getDataValue();
				if ( $referenceUrl instanceof StringValue ) {
					return '[' . $referenceUrl->getValue() . ' ' .
						$this->snakFormatter->formatSnak( $titleSnak ) . ']';
				}
			}
		}
		return null;
	}

}
