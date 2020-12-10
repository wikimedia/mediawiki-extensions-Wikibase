<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Describes objects emitting view placeholders for parts of the markup that aren't cacheable,
 * e.g. those that are language-specific.
 * This is typically used in conjunction with {@see \Wikibase\Repo\ParserOutput\TextInjector}.
 *
 * For an example
 * @see \Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView
 *
 * @license GPL-2.0-or-later
 */
interface ViewPlaceHolderEmitter {

	public const ERRONEOUS_PLACEHOLDER_VALUE = null;

	public function getPlaceholders(
		EntityDocument $entity,
		$revisionId,
		$languageCode
	);

}
