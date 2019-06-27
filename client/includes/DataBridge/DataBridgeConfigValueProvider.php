<?php

namespace Wikibase\Client\DataBridge;

use Wikibase\Lib\Modules\MediaWikiConfigValueProvider;

/**
 * @license GPL-2.0-or-later
 */
class DataBridgeConfigValueProvider implements MediaWikiConfigValueProvider {

	public function getKey() {
		return 'wbDataBridgeConfig';
	}

	public function getValue() {
		return [
			'hrefRegExp' => 'https://www\.wikidata\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
		];
	}

}
