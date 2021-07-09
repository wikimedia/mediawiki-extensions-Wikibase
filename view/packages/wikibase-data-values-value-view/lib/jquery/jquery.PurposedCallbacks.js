( function () {
	'use strict';

	/**
	 * An instance of `jQuery.PurposedCallbacks` is a list of `jQuery.Callbacks` instances, one per
	 * "purpose". The purposes are string identifiers for groups of callbacks. Callbacks can be
	 * registered for a purpose. Callbacks registered for one purpose will be executed together.
	 *
	 * This is conceptually similar to `jQuery.Deferred` but more flexible since it allows to define
	 * define custom purposes other than `done`, `fail` and `progress`.
	 * Also, there is an equivalent to `jQuery.Deferred.prototype.promise` which is
	 * `jQuery.PurposedCallbacks.prototype.facade`.
	 *
	 *     @example
	 *     function someAction() {
	 *         var callbacks = $.PurposedCallbacks( [ 'done', 'fail' ] );
	 *
	 *         someAsynchronousAction(
	 *             function() {
	 *                 callbacks.fire( 'done' );
	 *             },
	 *             function( errorMsg ) {
	 *                 callbacks.fire( 'fail', errorMsg );
	 *             }
	 *         );
	 *
	 *         // Only expose object for registering more callbacks, but not for firing them:
	 *         return callbacks.facade();
	 *     }
	 *     someAction()
	 *     .add( 'done', function() { alert( 'done' ) } );
	 *     .add( 'fail', function( reason ) { alert( 'error: ' + reason ) } );
	 *     .add( 'fail', function( reason ) { alert( 'ERROR!' } );
	 *
	 * @class jQuery.PurposedCallbacks
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {string[]|string} [predefinedPurposes] Allows to predefine which purposes are allowed.
	 *        If of type `string`, the parameter is assumed to be the callback options.
	 * @param {string} [callbackOptions] Same options as for `jQuery.Callbacks`, will be
	 *        forwarded.
	 *
	 * @throws {Error} if purpose is unknown.
	 */
	var SELF = function PurposedCallbacks( predefinedPurposes, callbackOptions ) {
		if ( !( this instanceof SELF ) ) {
			return new SELF( predefinedPurposes, callbackOptions );
		}

		if ( typeof predefinedPurposes === 'string' ) {
			callbackOptions = predefinedPurposes;
			predefinedPurposes = undefined;
		}

		/**
		 * Cache for .facade() member.
		 *
		 * @property {jQuery.PurposedCallbacks.Facade}
		 * @ignore
		 */
		var facade;

		/**
		 * Field names are purposes, each holding a jQuery.Callbacks instance.
		 *
		 * @property {Object}
		 * @ignore
		 */
		var callbacksPerPurpose = {};

		/**
		 * Registers a callback for some purpose.
		 *
		 * @param {string} purpose
		 * @param {Function|Function[]} callbacks
		 * @return {*}
		 *
		 * @throws {Error} if purposes are predefined via the constructor and "purpose" is not
		 *         one of them.
		 */
		this.add = function( purpose, callbacks ) {
			if ( predefinedPurposes && !this.has( purpose ) ) {
				throw new Error( 'Unknown purpose "' + purpose + '".' );
			}

			if ( !callbacksPerPurpose[ purpose ] ) {
				callbacksPerPurpose[ purpose ] = $.Callbacks( callbackOptions );
			}
			callbacksPerPurpose[ purpose ].add( callbacks );

			return this;
		};

		/**
		 * Removes a callback from a purpose.
		 *
		 * @param {string} purpose
		 * @param {Function|Function[]} callbacks
		 * @return {*}
		 */
		this.remove = function( purpose, callbacks ) {
			var callbackForPurpose = callbacksPerPurpose[ purpose ];
			if ( callbackForPurpose ) {
				// NOTE: We can't remove( callbacks ) even though it is documented to work this way.
				//  This is a bug (behavior not according to jQuery API documentation) which is
				//  still present in jQuery 2.0
				callbacks = Array.isArray( callbacks ) ? callbacks : [ callbacks ];
				callbackForPurpose.remove.apply( callbackForPurpose, callbacks );
			}
			return this;
		};

		/**
		 * Returns whether a given purpose is known or whether a specific callback has been
		 * registered for a purpose.
		 *
		 * @param {string} purpose
		 * @param {Function} [callback]
		 * @return {boolean}
		 */
		this.has = function( purpose, callback ) {
			if ( callback ) {
				var callbacksForPurpose = callbacksPerPurpose[ purpose ];
				return callbacksForPurpose && callbacksForPurpose.has( callback );
			}
			return $.inArray( purpose, this.purposes() ) > -1;
		};

		/**
		 * Returns all purposes used for registering callbacks. If purposes got defined via the
		 * constructor, then all those purposes will be returned. If purposes were not defined via
		 * the constructor, then all purposes used to register a callback via `add` will be
		 * returned, even if the callback got removed again and there are no callbacks left for the
		 * queue of that purpose.
		 *
		 * @return {string[]}
		 */
		this.purposes = function() {
			if ( predefinedPurposes ) {
				return predefinedPurposes.slice();
			}
			var usedPurposes = [];
			for ( var purpose in callbacksPerPurpose ) {
				usedPurposes.push( purpose );
			}
			return usedPurposes;
		};

		/**
		 * Fires the callbacks of the given purposes or of all purposes with the provided
		 * arguments. Context for the callbacks will be the PurposedCallbacks or the
		 * `PurposedCallbacks.Facade` instance the callback has been added with.
		 *
		 * @param {string|string[]} purposes
		 * @param {*[]} [args]
		 */
		this.fire = function( purposes, args ) {
			this.fireWith( this, purposes, args );
			return this;
		};

		/**
		 * Same as fire() but with the callbacks called in a given context.
		 *
		 * @param {*} context
		 * @param {string|string[]} purposes
		 * @param {Array} [args]
		 * @return {*}
		 *
		 * @throws {Error} in case `purposes` were defined in the constructor and a purpose given
		 *         here is not one of them.
		 */
		this.fireWith = function( context, purposes, args ) {
			if ( !Array.isArray( purposes ) ) {
				purposes = [ purposes ];
			}
			args = args || [];
			var missingPurposes = [];

			$.each( purposes, function( i, purpose ) {
				var callbacksForPurpose = callbacksPerPurpose[ purpose ];
				if ( callbacksForPurpose ) {
					callbacksForPurpose.fireWith( context, args );
				} else {
					missingPurposes.push( purpose );
				}
			} );

			if ( predefinedPurposes && missingPurposes.length ) {
				var unknownPurposes = $( missingPurposes ).not( predefinedPurposes );
				if ( unknownPurposes.length ) {
					throw new Error( 'Can not fire callbacks for unknown purposes.' );
				}
			}
			return this;
		};

		/**
		 * Returns a facade to the object, only allowing for adding/removing new callbacks but not
		 * allowing to fire them. Similar to `jQuery.Deferred's promise()`.
		 * The object returned here could for example be exposed as return value of a function which
		 * is initiating some asynchronous action. After the asynchronous action is done, the `fire`
		 * function will be called by the function. The code which received the facade by the
		 * function can only add and remove callbacks but not fire them.
		 *
		 * @return {jQuery.PurposedCallbacks.Facade}
		 */
		this.facade = function() {
			if ( !facade ) {
				facade = SELF.Facade( this );
			}
			return facade;
		};
	};

	/**
	 * Facade of `jQuery.PurposedCallbacks` which only allows access to the `add`, `remove`, `has`
	 * and `purposes` members. This is to `jQuery.PurposedCallbacks` what the "Promise" is to
	 * `jQuery.Deferred`.
	 *
	 * @class jQuery.PurposedCallbacks.Facade
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {jQuery.PurposedCallbacks} base
	 * @return {jQuery.PurposedCallbacks.Facade} Can be instantiated without `new`.
	 */
	SELF.Facade = function PurposedCallbacksFacade( base ) {
		if ( !( this instanceof SELF.Facade ) ) {
			return new SELF.Facade( base );
		}
		var self = this;

		$.each( [ 'add', 'remove', 'has', 'purposes' ], function( i, field ) {
			self[ field ] = function() {
				var result = base[ field ].apply( base, arguments );
				return result === base ? this : result;
			};
		} );
	};

	module.exports = SELF;

}() );
