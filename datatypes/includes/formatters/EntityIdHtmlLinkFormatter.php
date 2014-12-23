<?php

namespace Wikibase\Lib;

use Html;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;

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
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	public function __construct(
		FormatterOptions $options,
		LabelLookup $labelLookup,
		EntityTitleLookup $entityTitleLookup
	) {
		parent::__construct( $options, $labelLookup );

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
		$title = $this->entityTitleLookup->getTitleForId( $entityId );

		$attributes = array(
			'title' => $title->getPrefixedText(),
			'href' => $title->getLocalURL()
		);

		$label = $entityId->getSerialization();

		if ( $this->getOption( self::OPT_LOOKUP_LABEL ) ) {
			$itemLabel = $this->lookupEntityLabel( $entityId );
			if ( is_string( $itemLabel ) ) {
				$label = $itemLabel;
			} elseif ( !$title->exists() ) {
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
