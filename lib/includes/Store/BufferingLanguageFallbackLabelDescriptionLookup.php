<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermFallback;

/**
 * Only call the lookup once for the particular input
 */
class BufferingLanguageFallbackLabelDescriptionLookup implements LanguageFallbackLabelDescriptionLookup {

	private $buffer;

	/**
	 * @var LanguageFallbackLabelDescriptionLookup
	 */
	private $lookup;

	public function __construct( LanguageFallbackLabelDescriptionLookup $lookup ) {
		$this->buffer = new \MapCacheLRU( 666 ); // TODO: add Buffer interface or so
		$this->lookup = $lookup;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$bufferKey = $entityId->getSerialization() . '_label';

		if ( $this->buffer->has( $bufferKey ) ) {
			return $this->buffer->get( $bufferKey );
		}

		$label = $this->lookup->getLabel( $entityId );
		$this->buffer->set( $bufferKey, $label ); // TODO: serialize!

		return $label;
	}

	/**
	 * @param EntityId $entityId
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		$bufferKey = $entityId->getSerialization() . '_description';

		if ( $this->buffer->has( $bufferKey ) ) {
			return $this->buffer->get( $bufferKey );
		}

		$description = $this->lookup->getDescription( $entityId );
		$this->buffer->set( $bufferKey, $description ); // TODO: serialize!

		return $description;
	}

}
