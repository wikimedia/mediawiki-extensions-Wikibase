<?php

// This is a IDE helper to understand class aliasing.
// It should not be included anywhere.
// Actual aliasing happens in the entry point using class_alias.

namespace { throw new Exception( 'This code is not meant to be executed' ); }

namespace Wikibase\DataModel {

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class SimpleSiteLink extends SiteLink {}

}

namespace Wikibase\DataModel\Claim {

	/**
	 * @deprecated since 1.0, use the base class instead.
	 */
	class Statement extends \Wikibase\DataModel\Statement\Statement {}

}
