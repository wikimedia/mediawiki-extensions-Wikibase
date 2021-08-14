<?php

namespace Wikibase\Repo\EditEntity;

use IContextSource;
use InvalidArgumentException;
use RuntimeException;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;

/**
 * Interface to run a hook before and edit is saved.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
interface EditFilterHookRunner {

	/**
	 * Call EditFilterMergedContent hook, if registered.
	 *
	 * @param EntityDocument|EntityRedirect|null $new The entity or redirect we are trying to save
	 * @param IContextSource $context The request context for the edit
	 * @param string $summary The edit summary
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	public function run( $new, IContextSource $context, string $summary );

}
