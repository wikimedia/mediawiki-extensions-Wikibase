<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;

/**
 * OutputFormatSnakFormatterFactory is a service
 * for obtaining a SnakFormatter for a desired output format.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputFormatSnakFormatterFactory {

	/**
	 * @var WikibaseSnakFormatterBuilders
	 */
	private $snakFormatterBuilders;

	/**
	 * @param WikibaseSnakFormatterBuilders $snakFormatterBuilders
	 */
	public function __construct( WikibaseSnakFormatterBuilders $snakFormatterBuilders ) {
		$this->snakFormatterBuilders = $snakFormatterBuilders;
	}

	/**
	 * @param callable[] $builders maps formats to callable builders. Each builder must accept
	 *        three parameters, this OutputFormatSnakFormatterFactory, a format ID, and an FormatOptions object,
	 *        and return an instance of SnakFormatter suitable for the given output format.
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertValidSnakFormatterFormatBuilders( array $builders ) {
		foreach ( $builders as $format => $builder ) {
			if ( !is_string( $format ) ) {
				throw new InvalidArgumentException( '$builders must map type IDs to formatters.' );
			}

			if ( !is_callable( $builder ) ) {
				throw new InvalidArgumentException( '$builders must contain a callable builder for each format.' );
			}
		}
	}

	/**
	 * @throws InvalidArgumentException if one of the builders is invalid.
	 * @return array DataType builder specs
	 */
	private function getSnakFormatterFormatBuilders() {
		$snakFormatterFormatBuilders = $this->snakFormatterBuilders->getSnakFormatterBuildersForFormats();

		$this->assertValidSnakFormatterFormatBuilders( $snakFormatterFormatBuilders );

		return $snakFormatterFormatBuilders;
	}

	/**
	 * Returns an SnakFormatter for rendering snak values in the desired format
	 * using the given options.
	 *
	 * @param string $format Use the SnakFormatter::FORMAT_XXX constants.
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
	 * @param FormatterOptions $options
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return SnakFormatter
	 */
	public function getSnakFormatter(
		$format,
		WikibaseValueFormatterBuilders $valueFormatterBuilders,
		FormatterOptions $options
	) {
		$builders = $this->getSnakFormatterFormatBuilders();

		if ( !array_key_exists( $format, $builders ) ) {
			throw new InvalidArgumentException( "Unsupported format: $format" );
		}

		//TODO: cache instances based on an option hash
		$builder = $builders[$format];
		$instance = call_user_func( $builder, $format, $valueFormatterBuilders, $options );

		if( !( $instance instanceof SnakFormatter ) ) {
			throw new RuntimeException( get_class( $instance ) . ' does not implement SnakFormatter' );
		}

		return $instance;
	}

}
