<?php

namespace Wikibase\Lib;

use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\EntityLookup;
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
	protected $titleLookup;

	/**
	 * @param FormatterOptions $options
	 * @param array[] $entityInfo
	 * @param EntityLookup $entityLookup
	 * @param EntityTitleLookup $titleLookup
	 */
	public function __construct(
		FormatterOptions $options,
		$entityInfo,
		EntityLookup $entityLookup,
		EntityTitleLookup $titleLookup
	) {
		parent::__construct( $options, $entityInfo, $entityLookup );

		$this->titleLookup = $titleLookup;

		// TODO: Decide if this should be false by default
		$this->defaultOption( self::OPT_CHECK_EXISTS, true );
		$this->defaultOption( self::OPT_SHOW_UNDEFINED_INFO, true );
	}

	/**
	 * @param EntityId $entityId
	 * @param array $entityInfo
	 *
	 * @return string
	 *
	 * @see EntityIdFormatter::formatEntityId
	 * @see EntityInfoBuilder
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		$label = parent::formatEntityId( $entityId, $exists );
		$title = $this->titleLookup->getTitleForId( $entityId );

		if ( $title === null ) {
			$exists = false;
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
