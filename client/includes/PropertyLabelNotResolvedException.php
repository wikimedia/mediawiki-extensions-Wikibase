<?php

declare( strict_types = 1 );

namespace Wikibase\Client;

use Exception;
use Wikibase\Lib\MessageException;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyLabelNotResolvedException extends MessageException {

	public function __construct(
		string $label,
		string $languageCode,
		?string $message = null,
		Exception $previous = null
	) {
		parent::__construct(
			'wikibase-property-notfound',
			[ $label, $languageCode ],
			$message ?? "Could not find a property with label '$label'@$languageCode",
			$previous
		);
	}

}
