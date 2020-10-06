<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Lib\Changes\ItemChangeTrait;

/**
 * @license GPL-2.0-or-later
 */
class RepoItemChange extends RepoEntityChange {
	use ItemChangeTrait;
}
