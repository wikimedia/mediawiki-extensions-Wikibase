<?php

namespace Wikibase\Lib;

use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Formats entity IDs by generating the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityIdTitleFormatter extends EntityIdFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	protected $titleLookup;

	/**
	 * @param FormatterOptions $options
	 * @param EntityTitleLookup $titleLookup
	 */
	public function __construct( FormatterOptions $options, EntityTitleLookup $titleLookup ) {
		parent::__construct( $options );

		$this->titleLookup = $titleLookup;
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
		$title = $this->titleLookup->getTitleForId( $entityId );
		return $title->getFullText();
	}

}
