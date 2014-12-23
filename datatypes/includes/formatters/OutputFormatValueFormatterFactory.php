<?php
namespace Wikibase\Lib;
use InvalidArgumentException;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * OutputFormatValueFormatterFactory is a service
 * for obtaining a SnakFormatter for a desired output format.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class OutputFormatValueFormatterFactory {

	/**
	 * @var callable[]
	 */
	private $builders;

	/**
	 * @param callable[] $builders maps formats to callable builders. Each builder must accept
	 *        three parameters, this OutputFormatSnakFormatterFactory, a format ID, and an FormatOptions object,
	 *        and return an instance of ValueFormatter suitable for the given output format.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $builders ) {
		foreach ( $builders as $format => $builder ) {
			if ( !is_string( $format ) ) {
				throw new InvalidArgumentException( '$builders must map type IDs to formatters.' );
			}

			if ( !is_callable( $builder ) ) {
				throw new InvalidArgumentException( '$builders must contain a callable builder for each format.' );
			}
		}

		$this->builders = $builders;
	}

	/**
	 * Returns an ValueFormatter for rendering DataValues in the desired format
	 * using the given options.
	 *
	 * @param string $format Use the SnakFormatter::FORMAT_XXX constants.
	 * @param FormatterOptions $options
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return ValueFormatter
	 */
	public function getValueFormatter( $format, FormatterOptions $options ) {
		if ( !array_key_exists( $format, $this->builders ) ) {
			throw new InvalidArgumentException( "Unsupported format: $format" );
		}

		//TODO: cache instances based on an option hash
		$builder = $this->builders[$format];
		$instance = call_user_func( $builder, $this, $format, $options );


		if( !( $instance instanceof ValueFormatter ) ) {
			throw new RuntimeException( get_class( $instance ) . ' does not implement ValueFormatter' );
		}

		return $instance;
	}

}
