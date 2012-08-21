/**
 * JavaScript for user interface related stuff of the 'Wikibase' extension.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

// allow to use this module if the main wikibase module is not required for some reason.
window.wikibase = window.wikibase || {};

/**
 * Module for 'Wikibase' extensions user interface functionality.
 * Make sure this won't be overriden when loading two ui modules
 * in parallel without loading base module.
 * @var Object
 */
window.wikibase.ui = window.wikibase.ui || {
	/**
	 * @const states of element groups
	 * @enum number
	 */
	ELEMENT_STATE: {
		ENABLED: 1, // all elements are enabled
		DISABLED: 2, // all elements are disabled
		MIXED: 3
	}
};
