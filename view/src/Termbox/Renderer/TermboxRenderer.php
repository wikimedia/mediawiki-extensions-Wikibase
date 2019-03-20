<?php

namespace Wikibase\View\Termbox\Renderer;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;

/**
 * @license GPL-2.0-or-later
 */
interface TermboxRenderer {

	/**
	 * @throws TermboxRenderingException
	 *
	 * @param EntityId $entityId
	 * @param int $revision
	 * @param string $language
	 * @param string $editLink
	 * @param LanguageFallbackChain $preferredLanguages
	 *
	 * @return string
	 */
	public function getContent( EntityId $entityId, $revision, $language, $editLink, LanguageFallbackChain $preferredLanguages );

}
