<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;

/**
 * Only call the lookup once for the particular input
 */
class BufferingLabelDescriptionLookup implements LabelDescriptionLookup {

	private $buffer;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $lookup;

	public function __construct(LabelDescriptionLookup $lookup ) {
		$this->buffer = new \MapCacheLRU( 666 ); // TODO: add Buffer interface or so
		$this->lookup = $lookup;
	}

	public function getLabel( EntityId $entityId ) {
		$bufferKey = $entityId->getSerialization() . '_label';
		$label = $this->buffer->get( $bufferKey );
		if ( $label !== null ) {
			return $label;
		}
		$label = $this->lookup->getLabel( $entityId );
		$this->buffer->set( $bufferKey, $label ); // TODO: serialize
		return $label;
	}

	public function getDescription(EntityId $entityId) {
		$bufferKey = $entityId->getSerialization() . '_description';
		$description = $this->buffer->get( $bufferKey );
		if ( $description !== null ) {
			return $description;
		}
		$description = $this->lookup->getDescription( $entityId );
		$this->buffer->set( $bufferKey, $description ); // TODO: serialize
		return $description;
	}

}
