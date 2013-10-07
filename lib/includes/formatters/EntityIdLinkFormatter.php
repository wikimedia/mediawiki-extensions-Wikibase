<?php

namespace Wikibase\Lib;
use InvalidArgumentException;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\EntityId;
use Wikibase\EntityTitleLookup;

/**
 * Formats entity IDs by generating a wiki link to the corresponding page title.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityIdLinkFormatter extends EntityIdTitleFormatter {

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options
	 * @param EntityTitleLookup $titleLookup
	 *
	 * @internal param \Wikibase\EntityLookup $entityLookup
	 *
	 */
	public function __construct( FormatterOptions $options, EntityTitleLookup $titleLookup ) {
		parent::__construct( $options, $titleLookup );
	}

	/**
	 * Format an EntityId data value
	 *
	 * @since 0.4
	 *
	 * @param EntityId|EntityIdValue $value The value to format
	 *
	 * @return string
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		$title = parent::format( $value );

		return "[[$title]]";
	}

}

