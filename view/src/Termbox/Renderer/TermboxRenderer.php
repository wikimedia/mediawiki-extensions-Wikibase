<?php

namespace Wikibase\View\Termbox\Renderer;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @license GPL-2.0-or-later
 */
interface TermboxRenderer {

	/**
	 * @param EntityId $entityId
	 * @param int $revision
	 * @param string $language
	 * @param string $editLink
	 * @param TermLanguageFallbackChain $preferredLanguages
	 *
	 * @return string
	 * @throws TermboxRenderingException
	 *
	 */
	public function getContent( EntityId $entityId, $revision, $language, $editLink, TermLanguageFallbackChain $preferredLanguages );

}
