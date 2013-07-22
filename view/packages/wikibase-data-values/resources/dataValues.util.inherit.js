/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, util ) {
	'use strict';

	/**
	 * Helper to create a named function which will execute a given function.
	 *
	 * @param {string} name Name of the new function. All characters not matching [\w$] will be
	 *        removed.
	 * @param {Function} [originalFn] Function which will be executed by new function. If not given,
	 *        an empty function will be used instead.
	 * @return Function
	 *
	 * @throws {Error} If the given name has no characters matching [\w$].
	 */
	function createNamedFunction( name, originalFn ) {
		/* jshint evil: true */
		/* jshint unused: false */
		var namedFn,
			evilsSeed = originalFn || $.noop,
			fnName = name.replace( /(?:(^\d+)|[^\w$])/ig, '' );

		if( !fnName ) {
			// only bad characters were in the name!
			throw new Error( 'Bad function name given. At least one word character or $ required.' );
		}

		eval( 'namedFn = function ' + fnName +
			'(){ evilsSeed.apply( this, arguments ); }' );

		return namedFn; // value got assigned in eval
	}

	/**
	 * Helper for prototypical inheritance.
	 * @since 0.1
	 *
	 * @param {string} name (optional) The name of the new constructor. This is handy for debugging
	 *        purposes since instances of the constructor might be displayed under that name.
	 * @param {Function} base Constructor which will be used for the prototype chain. This function
	 *        will not be the constructor returned by the function but will be called by it.
	 * @param {Function} [constructor] for overwriting base constructor. Can be omitted.
	 * @param {Object} [members] properties overwriting or extending those of the base.
	 * @return Function Constructor of the new, extended type.
	 *
	 * @throws {Error} In case a malicious function name is given or a reserved word is used
	 */
	util.inherit = function( name, base, constructor, members ) {
		// the name is optional
		if( typeof name !== 'string' ) {
			members = constructor; constructor = base; base = name; name = false;
		}

		// allow to omit constructor since it can be inherited directly. But if given, require it as second parameter
		// for readability. If no constructor, second parameter is the prototype extension object.
		if( !members ) {
			if( $.isFunction( constructor ) ) {
				members = {};
			} else {
				members = constructor || {}; // also support case where no parameters but base are given
				constructor = false;
			}
		}
		// If no name is given, find suitable constructor name. We want proper names here, so
		// instances can easily be identified during debugging.
		var constructorName = name || constructor.name || ( base.name ? base.name + '_SubProto' : 'SomeInherited' ),
			prototypeName = base.name || 'SomeProto';

		// function we execute in our real constructor
		var NewConstructor = createNamedFunction( constructorName, constructor || base );

		// new constructor for avoiding direct use of base constructor and its potential side-effects
		var NewPrototype = createNamedFunction( prototypeName );
		NewPrototype.prototype = base.prototype;

		NewConstructor.prototype = $.extend(
			new NewPrototype(),
			{ constructor: NewConstructor }, // make sure "constructor" property is set properly...
			members // ... but allow members to overwrite "constructor"
		);
		return NewConstructor;
	};

	/**
	 * Throw a kind of meaningful error whenever the function should be overwritten when inherited.
	 * @throws Error
	 *
	 * @since 0.1
	 *
	 * @example:
	 * SomethingAbstract.prototype = {
	 *     someFunc: function( a, b ) { doSomething() },
	 *     someAbstractFunc: wb.utilities.abstractFunction
	 * };
	 */
	util.abstractMember = function() {
		throw new Error( 'Call to undefined abstract function' );
	};

}( jQuery, dataValues.util ) );
