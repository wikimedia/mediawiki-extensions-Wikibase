<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Formatters;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * @license GPL-2.0-or-later
 */
class NonExistingEntityIdHtmlBrokenLinkFormatter extends NonExistingEntityIdHtmlFormatter {

	/**
	 * @var EntityTitleTextLookup
	 */
	private $entityTitleTextLookup;

	/**
	 * @var EntityUrlLookup
	 */
	private $entityUrlLookup;

	/**
	 * @param string $deletedEntityMessagePrefix E.g. 'wikibase-deletedentity-'
	 * @param EntityTitleTextLookup $entityTitleTextLookup
	 * @param EntityUrlLookup $entityUrlLookup
	 */
	public function __construct(
		string $deletedEntityMessagePrefix,
		EntityTitleTextLookup $entityTitleTextLookup,
		EntityUrlLookup $entityUrlLookup
	) {
		parent::__construct( $deletedEntityMessagePrefix );
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->entityUrlLookup = $entityUrlLookup;
	}

	/**
	 * @param EntityId $entityId
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $entityId ): string {
		$attributes = [
			'title' => wfMessage(
				'red-link-title',
				$this->entityTitleTextLookup->getPrefixedText( $entityId )
			)->text(),
			'href' => $this->entityUrlLookup->getLinkUrl( $entityId ),
			'class' => 'new',
		];
		$messageSection = $this->getUndefinedInfoMessage( $entityId );
		return Html::element( 'a', $attributes, $entityId->getSerialization() ) . $messageSection;
	}
}
