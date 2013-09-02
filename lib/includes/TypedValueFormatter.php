<?php

namespace Wikibase\Lib;

use DataTypes\DataType;
use DataValues\DataValue;
use DataValues\IllegalValueException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\CachingEntityLoader;
use Wikibase\LanguageFallbackChain;
use Wikibase\Settings;
use Wikibase\WikiPageEntityLookup;

/**
 * Provides a string representation for a DataValue given its associated DataType.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @todo make this an interface used by PropertyValueSnakFormatter instead.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedValueFormatter {

	/**
	 * @param DataValue $dataValue
	 * @param DataType $dataType
	 * @param LanguageFallbackChain|string $language language code string or LanguageFallbackChain object
	 *
	 * @return string
	 */
	public function formatToString( DataValue $dataValue, DataType $dataType, $language ) {
		// TODO: update this code to obtain the string formatter as soon as corresponding changes
		// in the DataTypes library have been made.

		if ( $dataValue->getType() === 'bad' ) {
			throw new IllegalValueException( $dataValue->getReason() );
		}

		$valueFormatters = $dataType->getFormatters();
		$valueFormatter = reset( $valueFormatters );

		// FIXME: before we can properly use the DataType system some issues to its implementation need
		// to be solved. Once this is done, this evil if block and function it calls should go.
		if ( $valueFormatter === false && $dataType->getId() === 'wikibase-item' ) {
			$valueFormatter = $this->evilGetEntityIdFormatter( $language );
		}

		if ( $valueFormatter === false ) {
			$value = $dataValue->getValue();

			if ( is_string( $value ) ) {
				return $value;
			}

			// @todo: implement: error message or other error handling
			// @todo: implement value formatter for time!
			return '';
		}

		/**
		 * @var ValueFormatter $valueFormatter
		 */
		return $valueFormatter->format( $dataValue );
	}

	private function evilGetEntityIdFormatter( $language ) {
		$entityLookup = new CachingEntityLoader( new WikiPageEntityLookup( Settings::get( 'repoDatabase' ) ) );

		$idFormatter = new EntityIdFormatter( new FormatterOptions() );

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $language );

		$labelFormatter = new EntityIdLabelFormatter( $options, $entityLookup );
		$labelFormatter->setIdFormatter( $idFormatter );

		return $labelFormatter;
	}

}
