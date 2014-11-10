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
		$builders = $this->snakFormatterBuilders->getSnakFormatterBuildersForFormats();

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
