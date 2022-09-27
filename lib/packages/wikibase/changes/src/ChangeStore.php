<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Changes;

/**
 * Service interface for recording changes.
 *
 * @see @ref docs_topics_change-propagation for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
interface ChangeStore {

	public function saveChange( Change $change );

	public function deleteChangesByChangeIds( array $changeIds ): void;

}
