/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	// make this usable without base utilities
	wb.utilities = wb.utilities || {};

	/**
	 * Collection of jQuery extensions of the Wikibase extension
	 * @var Object
	 */
	wb.utilities.jQuery = wb.utilities.jQuery || {};

	/**
	 * Helper function to remove all css classes matching a regular expression.
	 *
	 * @since 0.1
	 *
	 * @param RegExp classNameRegex
	 * @return jQuery
	 */
	$.fn.removeClassByRegex = function( classNameRegex ) {
		this.each( function() {
			var subject = $( this ),
				newClasses = '';
			if( ! subject.attr( 'class' ) ) {
				return;
			}

			$.each( subject.attr( 'class' ).split( /\s+/ ), function( i, className ) {
				// check for each class whether it matches...
				if( ! className.match( classNameRegex ) ) {
					// ...if not, we re-add it
					newClasses += ' ' + className;
				}
			} );

			// override classes:
			subject.attr( 'class', $.trim( newClasses ) );
		} );

		return this;
	};

	/**
	 * Returns a function which executes a given function which should return a jQuery.Deferred or jQuery.Promise.
	 * The promise will be returned by the outer function as long as it is not resolved/rejected, therefore the inner
	 * function won't be called again until then.
	 * In addition, the returned promise will contain additional information in form of attached properties within a
	 * 'promisor' property attached to the returned Promise.
	 * Description of the different properties:
	 * - isOngoing: will be false after the first call and then be incremented starting with 1 after each further call
	 *              as long as the promise has not been resolved/rejected.
	 * - originalArguments: the arguments the function has been called with originally
	 *
	 * @param fn Function inner function which should return a jQuery.Promise or jQuery.Deferred
	 * @return Function calling the given Function fn or returning the promise returned by fn after it has been called
	 *         for the first time until the promise is resolved/rejected.
	 */
	$.PersistentPromisor = function( fn ) {
		/**
		 * Promise returned by the inner function. Set until it is resolved/rejected
		 * @var jQuery.Promise|null
		 */
		var promise = null;

		return function() {
			if( promise && promise.state() === 'pending' ) {
				// function fn called already and returned jQuery.Promise which isn't resolved yet...
				promise.promisor.isOngoing++; // ... add note that it is ongoing (promise existed before, increment)...
				return promise; // ... and return it
			}

			var ret = fn.apply( this, arguments );

			if( ret && $.isFunction( ret.promise ) ) {
				// return value of inner function is a jQuery.Promise/Deferred
				promise = ret.promise()
					.always( function() {
						promise = null; // don't need promise anymore when done
					} );

				promise.promisor = promise.promisor || {}; // allow inner function to extend these info
				promise.promisor = $.extend( promise.promisor, {
					isOngoing: false,
					originalArguments: arguments
				} );
				ret = promise;
			}
			return ret; // return jQuery.Promise or any other value returned by inner function
		};
	};

}( jQuery, mediaWiki, wikibase ) );
