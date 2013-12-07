<?php

namespace Wikibase\Test;

use Exception;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Term;
use Wikibase\TermIndex;
use Wikibase\PropertyLabelResolver;
use Wikibase\TermPropertyLabelResolver;

/**
 * @covers Wikibase\TermPropertyLabelResolver
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermPropertyLabelResolverTest extends PropertyLabelResolverTest {

	/**
	 * @param string $lang
	 * @param Term[] $terms
	 *
	 * @return PropertyLabelResolver
	 */
	public function getResolver( $lang, $terms ) {
		$resolver = new TermPropertyLabelResolver(
			$lang,
			new MockTermIndex( $terms ),
			new \HashBagOStuff(),
			3600,
			'testrepo:WBL\0.5alpha'
		);

		return $resolver;
	}


	//NOTE: actual tests are inherited from PropertyLabelResolver

}
