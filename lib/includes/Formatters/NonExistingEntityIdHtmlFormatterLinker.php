<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * @license GPL-2.0-or-later
 *
 * Format and link non existing entityIds
 */
class NonExistingEntityIdHtmlFormatterLinker implements EntityIdFormatter {

	/**
	 * @var NonExistingEntityIdHtmlFormatter
	 */
	private $entityIdHtmlFormatter;

	/**
	 * @var EntityTitleTextLookup
	 */
	private $entityTitleTextLookup;

	/**
	 * @var EntityUrlLookup
	 */
	private $entityUrlLookup;

	/**
	 * @var NonExistingEntityIdHtmlBrokenLinkFormatter
	 */
	private $entityIdHtmlBrokenLinkFormatter;

	public function __construct( EntityTitleTextLookup $entityTitleTextLookup, EntityUrlLookup $entityUrlLookup ) {
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->entityUrlLookup = $entityUrlLookup;
		$this->entityIdHtmlFormatter = new NonExistingEntityIdHtmlFormatter(
			'wikibase-deletedentity-'
		);
		$this->entityIdHtmlBrokenLinkFormatter = new NonExistingEntityIdHtmlBrokenLinkFormatter(
			'wikibase-deletedentity-',
			$this->entityTitleTextLookup,
			$this->entityUrlLookup
		);
	}

	public function formatEntityId( EntityId $entityId ) {
		if ( $entityId instanceof PropertyId ) {
			return $this->entityIdHtmlBrokenLinkFormatter->formatEntityId( $entityId );
		}
		return $this->entityIdHtmlFormatter->formatEntityId( $entityId );
	}
}
