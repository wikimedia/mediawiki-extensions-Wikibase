<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\ValueFormatterBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig < thiemo.maettig@wikimedia.de >
 */
class EntityIdFormatter extends ValueFormatterBase {

	/**
	 * Format an EntityId data value
	 *
	 * @since 0.4
	 *
	 * @param EntityId|EntityIdValue $value The Entity ID to format
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function format( $value ) {
		if ( $value instanceof EntityIdValue ) {
			$value = $value->getEntityId();
		}

		if ( !( $value instanceof EntityId ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityId.' );
		}

		return $this->formatEntityId( $value, $this->entityIdExists( $value ) );
	}

	/**
	 * TODO: Explain!
	 * TODO: Write test!
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param bool $exists
	 *
	 * @return string
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		return $entityId->getSerialization();
	}

	/**
	 * TODO: Explain!
	 * TODO: Write test!
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool Always true in this default implementation.
	 */
	protected function entityIdExists( EntityId $entityId ) {
		return true;
	}

}
