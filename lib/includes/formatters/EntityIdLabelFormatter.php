<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Lib\Store\UnresolvedRedirectException;

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
	const OPT_LOOKUP_LABEL = 'lookup';

	/**
	 * What we should do if we can't find the label.
	 */
	const OPT_LABEL_FALLBACK = 'fallback';

	const FALLBACK_PREFIXED_ID = 0;
	const FALLBACK_EMPTY_STRING = 1;
	const FALLBACK_NONE = 2;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var TermLookup
	 */
	protected $termLookup;

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options Supported options: OPT_LOOKUP_LABEL (boolean),
	 *        OPT_LABEL_FALLBACK (FALLBACK_XXX)
	 * @param EntityLookup $entityLookup
	 * @param TermLookup $termLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		FormatterOptions $options,
		EntityLookup $entityLookup,
		TermLookup $termLookup
	) {
		parent::__construct( $options );

		$this->entityLookup = $entityLookup;
		$this->termLookup = $termLookup;

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
		if ( $this->options->hasOption( 'languages' ) ) {
			$labels = $this->termLookup->getLabels( $entityId );

			$languageFallbackChain = $this->getOption( 'languages' );
			$extractedData = $languageFallbackChain->extractPreferredValue( $labels );

			if ( $extractedData === null ) {
				return false;
			} else {
				return $extractedData['value'];
			}
		} else {
			$lang = $this->getOption( self::OPT_LANG );
			return $this->termLookup->getLabel( $entityId, $lang );
		}
	}

}
