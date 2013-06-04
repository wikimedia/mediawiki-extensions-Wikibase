/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, dataValues ) {
	'use strict';

	var dv = dataValues;

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
	dv.util.inherit = function( name, base, constructor, members ) {
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
		// if no name is given, find suitable constructor's name
		name = name || constructor.name || ( base.name ? base.name + '_SubProto' : 'SomeInherited' );
		// make sure name is just a function name and not some executable JavaScript
		name = name.replace( /(?:(^\d+)|[^\w$])/ig, '' );

		if( !name ) { // only bad characters were in the name!
			throw new Error( 'Bad constructor name given. Only word characters and $ are allowed.' );
		}

		// function we execute in our real constructor created by evil eval:
		var evilsSeed,
			NewConstructor;

		evilsSeed = constructor || base;

		// for creating a named function with a variable name, there is just no other way...
		/* jshint evil: true */
		eval( 'NewConstructor = function ' + name +
			'(){ evilsSeed.apply( this, arguments ); }' );

		var NewPrototype = function(){}; // new constructor for avoiding base constructor and with it any side-effects
		NewPrototype.prototype = base.prototype;

		NewConstructor.prototype = $.extend(
			new NewPrototype(),
			members
		);

		// Make sure constructor property is set properly. The constructor has to be assigned
		// explicitly since doing it along the $.extend() above will be ignored in IE8.
		NewConstructor.prototype.constructor = NewConstructor;

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
	dv.util.abstractMember = function() {
		throw new Error( 'Call to undefined abstract function' );
	};

}( jQuery, dataValues ) );
