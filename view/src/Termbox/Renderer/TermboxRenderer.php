<?php

namespace Wikibase\View\Termbox\Renderer;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface TermboxRenderer {

	/**
	 * @param EntityId $entityId
	 * @param $language
	 *
	 * @return string
	 */
	public function getContent( EntityId $entityId, $language );

}
