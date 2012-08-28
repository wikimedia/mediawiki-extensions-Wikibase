/**
 * JavaScript for 'Wikibase' ui elements.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
( function( mw, wb, $, undefined ) {
"use strict";

/**
 * Base for prototypes handling frontend functionality.
 * Brings some convenience functions similar to jQuery's 'Widget'.
 * @constructor
 * @since 0.1
 *
 * @see wb.ui.Base.init() for parameter description
 */
wb.ui.Base = function( subject ) {
	if( subject !== undefined ) {
		this.init.apply( this, arguments );
	}
};
wb.ui.Base.prototype = {
	/**
	 * The root element of this UI object.
	 * @var jQuery
	 */
	_subject: null,

	/**
	 * Initializes the objects UI functionality for the given element.
	 * Usually this is called by the constructor, except if not all required parameters were given to the constructor
	 * or if the constructor was not used at all e.g. when using Object.create()
	 *
	 * If called on a already initialized object, this will destroy the object by calling destroy() and then initialize
	 * it again on the given subject. It is not encouraged to do so though. Normally a new object should be initialized
	 * to avoid problems with insufficient destroy() implementations.
	 *
	 * @param subject jQuery
	 */
	init: function( subject ) {
		if( this.isInitialized() ) {
			this.destroy();
		}
		this._subject = $( subject );
	},

	/**
	 * Returns true if the init() function was called or all necessary parameters have been passed to the constructor.
	 * If destroy() was called already, this will return false.
	 *
	 * @return Boolean
	 */
	isInitialized: function() {
		return this._subject !== null && !this.isDestroyed();
	},

	/**
	 * Destroys the UI functionality provided by this object
	 */
	destroy: function() {
		this._isDestroyed = true;
		// do not remove reference to subject since this could still be useful for the outside world!
	},

	/**
	 * Returns whether the destroy() function was called.
	 *
	 * @return Boolean
	 */
	isDestroyed: function() {
		return !!this._isDestroyed;
	},

	/**
	 * The root element of this UI object.
	 *
	 * @return jQuery
	 */
	getSubject: function() {
		return this._subject;
	}
};

} )( mediaWiki, wikibase, jQuery );
