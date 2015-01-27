
this.util = this.util || {};

( function( util, $ ) {
	'use strict';

	/**
	 * A service storing informations about the languages available for the content
	 * @class util.ContentLanguages
	 * @abstract
	 * @licence GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 */
	util.ContentLanguages = function() {
	};

	util.ContentLanguages.prototype = {
		/**
		 * Get all registered language codes
		 *
		 * Returns null if no information about accepted languages is known.
		 *
		 * @return {string[]|null}
		 */
		getAll: util.abstractMember,

		/**
		 * Get a name for a given language code
		 *
		 * Preferebly, this should be in a language the user understands (i. e. the UI language).
		 * Fallbacks are allowed, though. Returns null if the language code is not known.
		 *
		 * @return {string|null}
		 */
		getName: util.abstractMember
	};
}( util, jQuery ) );
