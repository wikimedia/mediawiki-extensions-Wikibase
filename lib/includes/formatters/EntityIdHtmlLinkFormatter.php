<?php

namespace Wikibase\Lib;

use Html;
use OutOfBoundsException;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\EntityTermLookup;
use Wikibase\EntityTitleLookup;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
 * @author Thiemo Mättig
 */
class EntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * If a more expensive check should be done when the entity info array is empty for an Entity
	 */
	const OPT_CHECK_EXISTS = 'exists';

	/**
	 * If an additional "does not exist" hint should be rendered
	 */
	const OPT_SHOW_UNDEFINED_INFO = 'undefinedinfo';

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @param FormatterOptions $options
	 * @param EntityTermLookup $entityTermLookup
	 * @param EntityTitleLookup|null $entityTitleLookup
	 */
	public function __construct(
		FormatterOptions $options,
		EntityTermLookup $entityTermLookup,
		EntityTitleLookup $entityTitleLookup = null
	) {
		parent::__construct( $options, $entityTermLookup );

		$this->entityTitleLookup = $entityTitleLookup;

		// TODO: Decide if this should be false by default
		$this->defaultOption( self::OPT_CHECK_EXISTS, false );
		$this->defaultOption( self::OPT_SHOW_UNDEFINED_INFO, true );
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 * @param bool $exists
	 *
	 * @return string
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		if ( isset( $this->entityTitleLookup ) ) {
			$title = $this->entityTitleLookup->getTitleForId( $entityId );
		} else {
			$title = Title::newFromText( $entityId->getPrefixedId() );
		}

		if ( $title === null ) {
			$exists = false;
		}

		$label = $entityId->getPrefixedId();

		if ( $exists && $this->getOption( self::OPT_RESOLVE_ID ) ) {
			try {
				$itemLabel = $this->lookupEntityLabel( $entityId );
				if ( is_string( $itemLabel ) ) {
					$label = $itemLabel;
				}
			} catch ( OutOfBoundsException $ex ) {
				$exists = false;
			}
		}

		if ( $exists && $this->getOption( self::OPT_CHECK_EXISTS ) ) {
			// TODO: This is expensive
			$exists = $title->exists();
		}

		/**
		 * TODO: Add class "extiw" on the client
		 * @see \Linker::link
		 */
		$attributes = array(
			'class' => $exists ? null : 'new',
			'title' => $title->getPrefixedText(),
			'href' => $title->getFullURL()
		);
		// TODO: Decide if it should be a red link or black text
		$html = \Html::element( 'a', $attributes, $label );

		// TODO: Decide if this should be done in an other patch
		if ( !$exists && $this->getOption( self::OPT_SHOW_UNDEFINED_INFO ) ) {
			$html = wfTemplate( 'wb-entity-undefinedinfo', $html,
				new \Message( 'parentheses', array(
				new \Message( 'wikibase-deletedentity-' . $entityId->getEntityType() ) ) ) );
		}

		return $html;
	}

}
