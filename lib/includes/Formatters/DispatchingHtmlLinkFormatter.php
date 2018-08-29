<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikimedia\Assert\Assert;

/**
 * DispatchingHtmlLinkFormatter is a formatter for EntityId Html Links.
 * It dispatches the formatter based on the entity type
 *
 * @license GPL-2.0-or-later
 */
class DispatchingHtmlLinkFormatter implements EntityIdFormatter {

	/**
	 * @var EntityIdHtmlLinkFormatter[]
	 */
	private $formatters;

	public function __construct( array $formatters ) {
		Assert::parameterElementType(EntityIdHtmlLinkFormatter::class, $formatters, '$formatters');

		$this->formatters = $formatters;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		if ( !isset($this->formatters[$entityType]) ) {
			return $this->formatters[$entityType]->formatEntityId( $entityId );
		}
	}
}
