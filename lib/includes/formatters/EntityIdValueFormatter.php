<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;

/**
 * A simple wrapper that forwards formatting of an EntityIdValue object to an EntityIdFormatter.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @see ValueFormatter::format
	 *
	 * Format an EntityIdValue
	 *
	 * @since 0.5
	 *
	 * @param EntityIdValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string Either plain text, wikitext or HTML, depending on the EntityIdFormatter
	 *  provided.
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityIdValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityIdValue.' );
		}

		return $this->entityIdFormatter->formatEntityId( $value->getEntityId() );
	}

}
