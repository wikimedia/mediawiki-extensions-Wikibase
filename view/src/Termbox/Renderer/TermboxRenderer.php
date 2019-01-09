<?php

namespace Wikibase\View\Termbox\Renderer;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface TermboxRenderer {

	/**
	 * @throws TermboxRenderingException
	 *
	 * @param EntityId $entityId
	 * @param string $language
	 * @param string $editLink
	 *
	 * @return string
	 */
	public function getContent( EntityId $entityId, $language, $editLink );

}
