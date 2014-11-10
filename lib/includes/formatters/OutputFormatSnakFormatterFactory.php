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
 */
class OutputFormatSnakFormatterFactory {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 */
	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
     * @param callable[] $builders maps formats to callable builders. Each builder must accept
     *        three parameters, this OutputFormatSnakFormatterFactory, a format ID, and an FormatOptions object,
     *        and return an instance of SnakFormatter suitable for the given output format.
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertValidSnakFormatterFormatBuilders( $builders ) {
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
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
	 *
	 * @return array DataType builder specs
	 */
	private function getSnakFormatterFormatBuilders(
		WikibaseValueFormatterBuilders $valueFormatterBuilders
	) {
		$builders = new WikibaseSnakFormatterBuilders(
			$this->propertyDataTypeLookup,
			$this->dataTypeFactory
		);

		$snakFormatterFormatBuilders = $builders->getSnakFormatterBuildersForFormats();

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
		$builders = $this->getSnakFormatterFormatBuilders( $valueFormatterBuilders );

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
