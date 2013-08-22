<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\Entity;
use Wikibase\EntityLookup;

/**
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityIdLabelFormatter extends ValueFormatterBase {

	const OPT_LABEL_FALLBACK = 'labelFallback';

	const FALLBACK_PREFIXED_ID = 0;
	const FALLBACK_EMPTY_STRING = 1;
	const FALLBACK_NONE = 2;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var EntityIdFormatter|null
	 */
	protected $idFormatter = null;

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options
	 * @param EntityLookup $entityLookup
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( FormatterOptions $options, EntityLookup $entityLookup ) {
		parent::__construct( $options );

		$this->entityLookup = $entityLookup;

		$this->defaultOption( self::OPT_LABEL_FALLBACK, self::FALLBACK_PREFIXED_ID );

		$fallbackOptionIsValid = in_array(
			$this->getOption( self::OPT_LABEL_FALLBACK ),
			array(
				 self::FALLBACK_PREFIXED_ID,
				 self::FALLBACK_EMPTY_STRING,
				 self::FALLBACK_NONE,
			)
		);

		if ( !$fallbackOptionIsValid ) {
			throw new InvalidArgumentException( 'Got an invalid label fallback option' );
		}
	}

	/**
	 * @param EntityIdFormatter $idFormatter
	 */
	public function setIdFormatter( EntityIdFormatter $idFormatter ) {
		$this->idFormatter = $idFormatter;
	}

	/**
	 * Format an EntityId data value
	 *
	 * @since 0.4
	 *
	 * @param EntityIdValue|EntityId $value The value to format
	 *
	 * @return string
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( $value instanceof EntityIdValue ) {
			$value = $value->getEntityId();
		}

		if ( !( $value instanceof EntityId ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityId.' );
		}

		$label = $this->lookupItemLabel( $value );

		if ( $label === false ) {
			switch ( $this->getOption( 'labelFallback' ) ) {
				case self::FALLBACK_EMPTY_STRING:
					$label = '';
					break;
				case self::FALLBACK_PREFIXED_ID:
					if ( $this->idFormatter === null ) {
						throw new RuntimeException( 'Cannot format the id using a prefix without the EntityIdFormatter being set' );
					}

					$label = $this->idFormatter->format( $value );
					break;
				default:
					// TODO: implement: return formatting error
					$label = 'TODO: ERROR: label not found';
			}
		}

		assert( is_string( $label ) );
		return $label;
	}

	/**
	 * Lookup a label for an entity
	 *
	 * @since 0.4
	 *
	 * @param EntityId
	 *
	 * @return string|boolean
	 */
	protected function lookupItemLabel( EntityId $entityId ) {
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( $entity === null ) {
			return false;
		}

		$languageFallbackChain = $this->getOption( self::OPT_LANG );

		// back-compat for usages where self::OPT_LANG is a string as a language code
		if ( is_string( $languageFallbackChain ) ) {
			return $entity->getLabel( $languageFallbackChain );
		}

		/**
		 * @var Entity $entity
		 */
		$extractedData = $languageFallbackChain->extractPreferredValue( $entity->getLabels() );

		if ( $extractedData === null ) {
			return false;
		} else {
			return $extractedData['value'];
		}
	}

}

