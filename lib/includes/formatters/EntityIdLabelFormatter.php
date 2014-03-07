<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\EntityLookup;
use Wikibase\LanguageFallbackChain;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 *
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
	 * @var array[]
	 */
	protected $entityInfo;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options Supported options: OPT_RESOLVE_ID (boolean),
	 *        OPT_LABEL_FALLBACK (FALLBACK_XXX)
	 * @param array[] $entityInfo
	 * @param EntityLookup $entityLookup
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		FormatterOptions $options,
		$entityInfo,
		EntityLookup $entityLookup
	) {
		parent::__construct( $options );

		$this->entityInfo = $entityInfo;
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
	 * @param EntityId $entityId
	 * @param bool $exists
	 *
	 * @throws FormattingException
	 * @return string
	 *
	 * @see EntityIdFormatter::formatEntityId
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		if ( $this->getOption( self::OPT_RESOLVE_ID ) ) {
			$label = $this->lookupEntityLabel( $entityId );
		}

		if ( !isset( $label ) ) {
			switch ( $this->getOption( self::OPT_LABEL_FALLBACK ) ) {
				case self::FALLBACK_PREFIXED_ID:
					$label = parent::formatEntityId( $entityId, $exists );
					break;
				case self::FALLBACK_EMPTY_STRING:
					$label = '';
					break;
				default:
					throw new FormattingException( 'No label found for ' . $entityId );
			}
		}

		assert( is_string( $label ) );
		return $label;
	}

	protected function entityIdExists( EntityId $entityId ) {
		if ( is_array( $this->entityInfo ) ) {
			$id = $entityId->getSerialization();
			return array_key_exists( $id, $this->entityInfo );
		}

		return parent::entityIdExists( $entityId );
	}

	protected function lookupEntityInfo( EntityId $entityId ) {
		if ( $this->entityIdExists( $entityId ) ) {
			$id = $entityId->getSerialization();
			return $this->entityInfo[$id];
		}

		return null;
	}

	protected function lookupEntityLabels( EntityId $entityId ) {
		$entityInfo = $this->lookupEntityInfo( $entityId );
		if ( is_array( $entityInfo ) && array_key_exists( 'labels', $entityInfo ) ) {
			return $entityInfo['labels'];
		}

		return null;
	}

	/**
	 * Lookup a label for an entity
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	protected function lookupEntityLabel( EntityId $entityId ) {
		$labels = $this->lookupEntityLabels( $entityId );
		if ( is_array( $labels ) ) {
			if ( $this->options->hasOption( 'languages' ) ) {
				/* @var LanguageFallbackChain $languageFallbackChain */
				$languageFallbackChain = $this->getOption( 'languages' );
				$labelData = $languageFallbackChain->extractPreferredValue( $labels );
			} else {
				$lang = $this->getOption( self::OPT_LANG );
				if ( isset( $labels[$lang] ) ) {
					$labelData = $labels[$lang];
				}
			}

			if ( isset( $labelData ) ) {
				return $labelData['value'];
			}
		}

		// TODO: $this->entityLookup is currently unused
		return null;
	}

}
