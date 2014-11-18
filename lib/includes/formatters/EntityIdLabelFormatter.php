<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatter extends EntityIdFormatter {

	/**
	 * Whether we should try to find the label of the entity
	 */
	const OPT_LOOKUP_LABEL = 'lookup';

	/**
	 * What we should do if we can't find the label.
	 */
	const OPT_LABEL_FALLBACK = 'fallback';

	const FALLBACK_PREFIXED_ID = 0;
	const FALLBACK_EMPTY_STRING = 1;
	const FALLBACK_NONE = 2;

	/**
	 * @var LabelLookup
	 */
	private $labelLookup;

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options Supported options: OPT_LOOKUP_LABEL (boolean),
	 *        OPT_LABEL_FALLBACK (FALLBACK_XXX)
	 * @param LabelLookup $labelLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( FormatterOptions $options, LabelLookup $labelLookup ) {
		parent::__construct( $options );

		$this->defaultOption( self::OPT_LOOKUP_LABEL, true );
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

		if ( !is_bool( $this->getOption( self::OPT_LOOKUP_LABEL ) ) ) {
			throw new InvalidArgumentException( 'Bad value for OPT_LOOKUP_LABEL option: must be a boolean' );
		}

		$this->labelLookup = $labelLookup;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @throws FormattingException
	 * @return string
	 */
	protected function formatEntityId( EntityId $entityId ) {
		$label = null;

		if ( $this->getOption( self::OPT_LOOKUP_LABEL ) ) {
			try {
				$label = $this->lookupEntityLabel( $entityId );
			} catch ( OutOfBoundsException $ex ) {
				/* Use fallbacks below */
			}
		}

		// @fixme check if the entity is deleted and format differently?
		if ( !is_string( $label ) ) {
			switch ( $this->getOption( self::OPT_LABEL_FALLBACK ) ) {
				case self::FALLBACK_PREFIXED_ID:
					$label = $entityId->getSerialization();
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
	 * Lookup a label for an entity
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException If an entity with that ID could not be loaded.
	 * @return string|bool False if no label was found in the language or language fallback chain.
	 */
	protected function lookupEntityLabel( EntityId $entityId ) {
		try {
			return $this->labelLookup->getLabel( $entityId );
		} catch ( OutOfBoundsException $e ) {
			return false;
		} catch ( StorageException $ex ) {
			// @todo maybe handle formatting in this in a better way.
			throw new OutOfBoundsException( "An Entity with the id $entityId "
				. "could not be loaded." );
		}
	}

}
