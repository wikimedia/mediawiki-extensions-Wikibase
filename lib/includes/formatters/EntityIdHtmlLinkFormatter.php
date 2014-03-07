<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\EntityLookup;
use Wikibase\EntityTitleLookup;

/**
 * Formats entity IDs by generating an html link to the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
 */
class EntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * TODO: Doc!
	 */
//	const OPT_UNDEFINED_INFO = 'undefinedInfo';

	/**
	 * @var EntityTitleLookup
	 */
	protected $titleLookup;

	/**
	 * @param FormatterOptions $options
	 * @param EntityLookup $entityLookup
	 * @param EntityTitleLookup $titleLookup
	 */
	public function __construct(
		FormatterOptions $options,
		EntityLookup $entityLookup,
		EntityTitleLookup $titleLookup
	) {
		parent::__construct( $options, $entityLookup );

		$this->titleLookup = $titleLookup;
	}

	/**
	 * @param EntityId $entityId
	 * @param bool $exists
	 *
	 * @return string
	 *
	 * @see EntityIdFormatter::formatEntityId
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		$contents = parent::formatEntityId( $entityId, $exists );

		$title = $this->titleLookup->getTitleForId( $entityId );
		/**
		 * TODO: Add class "extiw"
		 * @see \Linker::link
		 */
		$attributes = array(
			'class' => $exists ? null : 'new',
			'href' => $title->getFullURL()
		);
		$html = \Html::element( 'a', $attributes, $contents );

		// TODO: Do this in an other patch
		// TODO: Make this a template
//		if ( !$exists && $this->getOption( self::OPT_UNDEFINED_INFO ) ) {
//			$html .= ' ';
//			$html .= \Html::element( 'span', array( 'class' => 'wb-entity-undefinedinfo' ),
//				new \Message( 'parentheses', array(
//					new \Message( 'wikibase-deletedentity-' . $entityId->getEntityType() ) ) ) );
//		}

		return $html;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 *
	 * @see EntityIdFormatter::entityIdExists
	 */
	protected function entityIdExists( EntityId $entityId ) {
		// TODO: This is expensive.
		return $this->titleLookup->getTitleForId( $entityId )->exists();
	}

}
