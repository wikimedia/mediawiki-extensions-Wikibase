<?php
namespace Wikibase\Lib;
use DataTypes\DataType;
use DataValues\DataValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use Wikibase\Snak;

/**
 * SnakFormatterFactory is a service interface that defines a facility
 * for obtaining a SnakFormatter for a desired output format.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SnakFormatterFactory {

	const FORMAT_PLAIN = CONTENT_FORMAT_TEXT;
	const FORMAT_WIKI = CONTENT_FORMAT_WIKITEXT;
	const FORMAT_HTML = CONTENT_FORMAT_HTML;
	const FORMAT_JSON = CONTENT_FORMAT_JSON;
	const FORMAT_HTML_WIDGET = 'text/html; disposition=widget';

	/**
	 * @var callable[]
	 */
	private $builders;

	/**
	 * @param callable[] $builders maps formats to callable builders. Each builder must accept
	 *        three parameters, this SnakFormatterFactory, a format ID, and an FormatOptions object,
	 *        and return an instance of OldSnakFormatter.
	 *
	 * @throws \InvalidArgumentException
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
	 * Returns an OldSnakFormatter for rendering snak values in the desired format
	 * using the given options.
	 *
	 * @param string           $format The desired format, use the FORMAT_XXX constants.
	 * @param FormatterOptions $options
	 *
	 * @throws \InvalidArgumentException
	 * @return SnakFormatter
	 */
	public function getFormatter( $format, FormatterOptions $options = null ) {
		if ( !array_key_exists( $format, $this->builders ) ) {
			throw new \InvalidArgumentException( "Unsupported format: $format" );
		}

		//TODO: cache instances based on an option hash
		$builder = $this->builders[$format];
		$instance = call_user_func( $builder, $this, $format, $options );

		assert( $instance instanceof SnakFormatter );

		return $instance;
	}

}