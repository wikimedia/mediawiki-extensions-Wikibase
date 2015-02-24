<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class EntityIdValueFormatter implements ValueFormatter {

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @param EntityIdFormatter $entityIdFormatter
	 */
	public function __construct( EntityIdFormatter $entityIdFormatter ) {
		$this->entityIdFormatter = $entityIdFormatter;
	}

	/**
	 * Format an EntityIdValue
	 *
	 * @since 0.5
	 *
	 * @param EntityIdValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityIdValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityIdValue.' );
		}

		return $this->entityIdFormatter->formatEntityId( $value->getEntityId() );
	}

}
