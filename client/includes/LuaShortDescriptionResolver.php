<?php

namespace Wikibase\Client;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\TitleAttributeResolver;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\Title;
use Wikibase\Client\Store\DescriptionLookup;

/**
 * Handles the shortDescription attribute on mw.title objects in Lua modules.
 *
 * @license GPL-2.0-or-later
 */
class LuaShortDescriptionResolver extends TitleAttributeResolver {

	private DescriptionLookup $descriptionLookup;

	public function __construct( DescriptionLookup $descriptionLookup ) {
		$this->descriptionLookup = $descriptionLookup;
	}

	/**
	 * @param LinkTarget $target
	 * @return string|null
	 */
	public function resolve( LinkTarget $target ) {
		$title = Title::newFromLinkTarget( $target );
		if ( !$title->canExist() ) {
			return null;
		}

		$this->incrementExpensiveFunctionCount();
		$this->addTemplateLink( $title );

		return $this->descriptionLookup->getDescription( $title, DescriptionLookup::SOURCE_LOCAL );
	}
}
