<?php

namespace Wikibase\Lib;

use Html;
use OutOfBoundsException;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
 * @author Thiemo Mätti
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityTitleLookup|null
	 */
	protected $entityTitleLookup;

	public function __construct(
		FormatterOptions $options,
		LabelLookup $labelLookup,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup = null
	) {
		parent::__construct( $options, $labelLookup );

		$this->entityLookup = $entityLookup;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	protected function formatEntityId( EntityId $entityId ) {
		try {
			$labelText = $this->getLabel( $entityId );
		} catch ( OutOfBoundsException $ex ) {
			if ( !$this->entityLookup->hasEntity( $entityId ) ) {
				return $this->getHtmlForNonExistent( $entityId );
			} else {
				$labelText = $entityId->getSerialization();
			}
		}

		$attributes = $this->buildAttributes( $entityId );

		return Html::element( 'a', $attributes, $labelText );
	}

	private function buildAttributes( EntityId $entityId ) {
		$title = $this->getTitle( $entityId );

		return array(
			'title' => $title->getPrefixedText(),
			'href' => $title->getLocalURL()
		);
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	private function getTitle( EntityId $entityId ) {
		if ( isset( $this->entityTitleLookup ) ) {
			return $this->entityTitleLookup->getTitleForId( $entityId );
		} else {
			return Title::newFromText( $entityId->getSerialization() );
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string|false
	 */
	private function getLabel( EntityId $entityId ) {
		$label = null;

		if ( $this->getOption( self::OPT_LOOKUP_LABEL ) ) {
			$label = $this->labelLookup->getLabel( $entityId );
		}

		return $label;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getHtmlForNonExistent( EntityId $entityId ) {
		$attributes = array( 'class' => 'wb-entity-undefinedinfo' );

		$message = wfMessage( 'parentheses',
			wfMessage( 'wikibase-deletedentity-' . $entityId->getEntityType() )->text()
		);

		$undefinedInfo = Html::element( 'span', $attributes, $message );

		$separator = wfMessage( 'word-separator' )->text();
		return $entityId->getSerialization() . $separator . $undefinedInfo;
	}

}
