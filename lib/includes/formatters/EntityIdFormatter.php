<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\ValueFormatterBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class EntityIdFormatter extends ValueFormatterBase {

	/**
	 * Format an EntityId data value
	 *
	 * @since 0.4
	 *
	 * @param EntityId|EntityIdValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function format( $value ) {
		if ( $value instanceof EntityIdValue ) {
			$value = $value->getEntityId();
		}

		if ( !( $value instanceof EntityId ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityId or EntityIdValue.' );
		}

		return $this->formatEntityId( $value, $this->entityIdExists( $value ) );
	}

	/**
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
