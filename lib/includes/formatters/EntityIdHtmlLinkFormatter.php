<?php

namespace Wikibase\Lib;

use Html;
use InvalidArgumentException;
use OutOfBoundsException;
use Title;
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
	 * @var EntityTitleLookup|null
	 */
	protected $entityTitleLookup;

	/**
	 * @param FormatterOptions $options
	 * @param EntityLookup $entityLookup
	 * @param EntityTitleLookup|null $entityTitleLookup
	 */
	public function __construct(
		FormatterOptions $options,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup = null
	) {
		parent::__construct( $options, $entityLookup );

		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * Format an EntityId data value
	 *
	 * @param EntityId|EntityIdValue $value The value to format
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		$value = $this->unwrapEntityId( $value );

		if ( isset( $this->entityTitleLookup ) ) {
			$title = $this->entityTitleLookup->getTitleForId( $value );
		} else {
			$title = Title::newFromText( $value->getPrefixedId() );
		}
		$attributes = array(
			'title' => $title->getPrefixedText(),
			'href' => $title->getLocalURL()
		);

		$label = $value->getPrefixedId();

		if ( $this->getOption( self::OPT_LOOKUP_LABEL ) ) {
			try {
				$itemLabel = $this->lookupItemLabel( $value );
				if ( is_string( $itemLabel ) ) {
					$label = $itemLabel;
				}
			} catch ( OutOfBoundsException $ex ) {
				$attributes['class'] = 'new';
			}
		}

		$html = Html::element( 'a', $attributes, $label );

		return $html;
	}

}
