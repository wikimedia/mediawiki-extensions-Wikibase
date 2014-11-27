<?php
namespace Wikibase\Lib;
use InvalidArgumentException;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * OutputFormatIdFormatterFactory is a service
 * for obtaining a EntityIdFormatter for a desired output format.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class OutputFormatIdFormatterFactory {

	/**
	 * @var callable[]
	 */
	private $builders;

	/**
	 * @param callable[] $builders maps formats to callable builders. Each builder must accept
	 *        three parameters, this factory, the format, and an FormatOptions object,
	 *        and return an instance of EntityIdFormatter suitable for the given output format.
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
	 * Returns an EntityIdFormatter for rendering DataValues in the desired format
	 * using the given options.
	 *
	 * @param string $format Use the SnakFormatter::FORMAT_XXX constants.
	 * @param FormatterOptions $options
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return EntityIdFormatter
	 */
	public function getIdFormatter( $format, FormatterOptions $options ) {
		if ( !array_key_exists( $format, $this->builders ) ) {
			throw new InvalidArgumentException( "Unsupported format: $format" );
		}

		//TODO: cache instances based on an option hash
		$builder = $this->builders[$format];
		$instance = call_user_func( $builder, $this, $format, $options );


		if( !( $instance instanceof EntityIdFormatter ) ) {
			throw new RuntimeException( get_class( $instance ) . ' does not implement EntityIdFormatter' );
		}

		return $instance;
	}

}
