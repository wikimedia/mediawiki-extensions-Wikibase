<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\EntityLookup;
use Wikibase\LanguageFallbackChain;

/**
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 *
 * @todo: once this is no longer used directly, rename it to EntityIdValueFormatter
 * @todo: add support for language fallback chains
 */
class EntityIdLabelFormatter extends EntityIdFormatter {

	/**
	 * Whether we should try to find the label of the entity
	 */
	const OPT_RESOLVE_ID = 'resolveEntityId';

	/**
	 * What we should do if we can't find the label.
	 */
	const OPT_LABEL_FALLBACK = 'labelFallback';

	const FALLBACK_PREFIXED_ID = 0;
	const FALLBACK_EMPTY_STRING = 1;
	const FALLBACK_NONE = 2;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options Supported options: OPT_RESOLVE_ID (boolean),
	 *        OPT_LABEL_FALLBACK (FALLBACK_XXX)
	 * @param EntityLookup $entityLookup
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( FormatterOptions $options, EntityLookup $entityLookup ) {
		parent::__construct( $options );

		$this->entityLookup = $entityLookup;

		$this->defaultOption( self::OPT_RESOLVE_ID, true );
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
			throw new InvalidArgumentException( 'Bad value for OPT_LABEL_FALLBACK option' );
		}

		if ( !is_bool( $this->getOption( self::OPT_RESOLVE_ID ) ) ) {
			throw new InvalidArgumentException( 'Bad value for OPT_RESOLVE_ID option: must be a boolean' );
		}
	}

	/**
	 * @param EntityIdFormatter $idFormatter
	 * @deprecated
	 * @todo remove this when it's no longer being called
	 */
	public function setIdFormatter( EntityIdFormatter $idFormatter ) {
		// noop
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
		if ( $value instanceof EntityIdValue ) {
			$value = $value->getEntityId();
		}

		if ( !( $value instanceof EntityId ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityId or EntityIdValue.' );
		}

		if ( $this->getOption( self::OPT_RESOLVE_ID )  ) {
			$label = $this->lookupItemLabel( $value );
		} else {
			$label = false;
		}

		if ( $label === false ) {
			switch ( $this->getOption( self::OPT_LABEL_FALLBACK ) ) {
				case self::FALLBACK_EMPTY_STRING:
					$label = '';
					break;
				case self::FALLBACK_PREFIXED_ID:
					$label = $value->getPrefixedId();
					break;
				default:
					throw new FormattingException( 'No label found for ' . $value );
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

		/* @var LanguageFallbackChain $languageFallbackChain */
		if ( $this->options->hasOption( 'languages' ) ) {
			$languageFallbackChain = $this->getOption( 'languages' );

			$extractedData = $languageFallbackChain->extractPreferredValue( $entity->getLabels() );

			if ( $extractedData === null ) {
				return false;
			} else {
				return $extractedData['value'];
			}
		} else {
			$lang = $this->getOption( self::OPT_LANG );
			return $entity->getLabel( $lang );
		}
	}

}

