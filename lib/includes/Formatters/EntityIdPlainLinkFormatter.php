<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Formats entity IDs by generating a wiki link to the corresponding page title
 * without display text. This link can contain a namespace like [[Property:P42]].
 * HtmlPageLinkRendererEndHookHandler requires this exact format.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class EntityIdPlainLinkFormatter extends EntityIdTitleFormatter {

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string Wikitext
	 */
	public function formatEntityId( EntityId $entityId ) {
		$title = parent::formatEntityId( $entityId );

		return "[[$title]]";
	}

}
