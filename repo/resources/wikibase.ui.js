/**
 * JavaScript for user interface related stuff of the 'Wikibase' extension.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.js
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
 * @var Object
 */
window.wikibase.ui = window.wikibase.ui || {}; // make sure this won't override when loading two ui
                                               // modules in parallel without loading base module.
