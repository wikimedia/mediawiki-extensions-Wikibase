<?php

namespace Wikibase\Lib;

use EntityTermLookup;
use InvalidArgumentException;
use OutOfBoundsException;
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
 * @author Thiemo Mättig
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
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var EntityTermLookup
	 */
	protected $entityTermLookup;

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options Supported options: OPT_RESOLVE_ID (boolean),
	 *        OPT_LABEL_FALLBACK (FALLBACK_XXX)
	 * @param EntityLookup $entityLookup
	 * @param EntityTermLookup|null $entityTermLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		FormatterOptions $options,
		EntityLookup $entityLookup,
		$entityTermLookup
	) {
		parent::__construct( $options );

		$this->entityLookup = $entityLookup;
		$this->entityTermLookup = $entityTermLookup;

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
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 * @param bool $exists
	 *
	 * @throws FormattingException
	 * @return string
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		if ( $this->getOption( self::OPT_RESOLVE_ID ) ) {
			$label = $this->lookupEntityLabel( $entityId );
		} else {
			$label = null;
		}

		if ( !is_string( $label ) ) {
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

		return $label;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	protected function entityIdExists( EntityId $entityId ) {
		if ( $this->entityTermLookup !== null ) {
			try {
				// FIXME: This "misuse" of the interface is not nice.
				$this->entityTermLookup->getLabelForId( $entityId, null );
			} catch ( OutOfBoundsException $ex ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string|null
	 */
	protected function lookupEntityLabel( EntityId $entityId ) {
		if ( $this->entityTermLookup !== null ) {
			if ( $this->options->hasOption( 'languages' ) ) {
				/* @var LanguageFallbackChain $languageFallbackChain */
				$languageFallbackChain = $this->getOption( 'languages' );

				return $this->entityTermLookup->getLabelValueForId( $entityId, $languageFallbackChain );
			} elseif ( $this->options->hasOption( self::OPT_LANG ) ) {
				$languageCode = $this->getOption( self::OPT_LANG );

				return $this->entityTermLookup->getLabelForId( $entityId, $languageCode );
			}
		}

		return null;
	}

}
