<?php

namespace Wikibase\Repo\Modules;

use ResourceLoaderContext;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface MwConfigModuleWorker {

	/**
	 * @since 0.5
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return mixed
	 */
	public function getValue( ResourceLoaderContext $context );

}
