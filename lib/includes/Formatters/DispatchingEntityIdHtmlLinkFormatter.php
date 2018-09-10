<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikimedia\Assert\Assert;

/**
 * DispatchingEntityIdHtmlLinkFormatter is a formatter for EntityId Html Links.
 * It dispatches the formatter based on the entity type
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityIdHtmlLinkFormatter implements EntityIdFormatter {

	/**
	 * @var EntityIdFormatter[]
	 */
	private $formatters;

	/**
	 * @var EntityIdFormatter
	 */
	private $defaultFormatter;

	/**
	 * @param EntityIdFormatter[] $formatters
	 * @param EntityIdFormatter $defaultFormatter
	 */
	public function __construct( array $formatters, EntityIdFormatter $defaultFormatter ) {
		Assert::parameterElementType( EntityIdFormatter::class, $formatters, '$formatters' );

		$this->formatters = $formatters;
		$this->defaultFormatter = $defaultFormatter;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		if ( !isset( $this->formatters[$entityType] ) ) {
			return $this->defaultFormatter->formatEntityId( $entityId );
		}
		return $this->formatters[$entityType]->formatEntityId( $entityId );
	}

}
