/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $, undefined ) {
	'use strict';

	/**
	 * Module for 'DataValues' extensions utilities.
	 * @since 0.1
	 * @type Object
	 */
	dv.util = {};

	/**
	 * Helper for prototypal inheritance.
	 * @since 0.1
	 *
	 * @param {Function} base Constructor which will be used for the prototype chain.
	 * @param {Function} [constructor] for overwriting base constructor. Can be omitted.
	 * @param {Object} [members] properties overwriting or extending those of the base.
	 * @return Function Constructor of the new, extended type.
	 */
	dv.util.inherit = function( base, constructor, members ) {
		// allow to omit constructor since it can be inherited directly. But if given, require it as
		// second parameter for readability. If no constructor, second parameter is the prototype
		// extension object.
		if( members === undefined ) {
			if( $.isFunction( constructor ) ) {
				members = {};
			} else {
				members = constructor || {}; // also support case where no parameters but base are given
				constructor = false;
			}
		}
		var NewConstructor = constructor || function() { base.apply( this, arguments ); };

		var NewPrototype = function(){}; // new constructor for avoiding base constructor and with it any side-effects
		NewPrototype.prototype = base.prototype;

		NewConstructor.prototype = $.extend(
			new NewPrototype(),
			{ constructor: NewConstructor }, // make sure constructor property is set properly, can be overwritten from members
			members
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
	dv.util.abstractMember = function() {
		throw new Error( 'Call to undefined abstract function' );
	};

}( dataValues, jQuery ) );
