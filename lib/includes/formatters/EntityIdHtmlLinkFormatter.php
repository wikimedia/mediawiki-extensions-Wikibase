<?php

namespace Wikibase\Lib;

use Html;
use OutOfBoundsException;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

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
	 * @var EntityTitleLookup|null
	 */
	protected $entityTitleLookup;

	public function __construct(
		FormatterOptions $options,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup = null
	) {
		parent::__construct( $options, $entityLookup );

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
		if ( isset( $this->entityTitleLookup ) ) {
			$title = $this->entityTitleLookup->getTitleForId( $entityId );
		} else {
			$title = Title::newFromText( $entityId->getSerialization() );
		}
		$attributes = array(
			'title' => $title->getPrefixedText(),
			'href' => $title->getLocalURL()
		);

		$label = $entityId->getSerialization();

		if ( $this->getOption( self::OPT_LOOKUP_LABEL ) ) {
			try {
				$itemLabel = $this->lookupEntityLabel( $entityId );
				if ( is_string( $itemLabel ) ) {
					$label = $itemLabel;
				}
			} catch ( OutOfBoundsException $ex ) {
				return $this->getHtmlForNonExistent( $entityId );
			}
		}

		$html = Html::element( 'a', $attributes, $label );

		return $html;
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
