<?php

namespace Wikibase\View;

/**
 * Describes an EntityTermsView providing cacheable HTML, and placeholders for parts that aren't cacheable.
 * This is typically used in conjunction with {@see \Wikibase\Repo\ParserOutput\TextInjector}.
 *
 * For an example
 * @see \Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView
 *
 * @license GPL-2.0-or-later
 */
interface CacheableEntityTermsView extends EntityTermsView, ViewPlaceHolderEmitter {
}
