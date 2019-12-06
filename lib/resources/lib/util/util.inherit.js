/**
 * @class util
 * @singleton
 */
this.util = this.util || {};

( function( util ) {
	'use strict';

	/**
	 * Extends an object with the attributes of another object.
	 * @ignore
	 *
	 * @param {Object} target
	 * @param {Object} source
	 * @return {Object}
	 */
	function extend( target, source ) {
		for( var v in source ) {
			if( source.hasOwnProperty( v ) ) {
				target[v] = source[v];
			}
		}
		return target;
	}

	/**
	 * Helper to create a function which will execute a given function.
	 * @ignore
	 *
	 * @param {Function} [originalFn=function() {}] Optional function which will be executed by new
	 *        function.
	 * @return {Function}
	 */
	function createFunction( originalFn ) {
		return originalFn
			? function() { originalFn.apply( this, arguments ); }
			: function() {};
	}

	/**
	 * Helper for prototypical inheritance.
	 * @license GPL-2.0+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @param {string|Function} [nameOrBase] The name of the new constructor (currently not used).
	 *        This is handy for debugging purposes since instances of the constructor might be
	 *        displayed under that name.
	 *        If a function is provided, it is assumed to be the constructor to be used for the
	 *        prototype chain (see next argument).
	 * @param {Function} [baseOrConstructor] Constructor which will be used for the prototype
	 *        chain. This function will not be the constructor returned by the function but will be
	 *        called by it.
	 *        If no name is provided, this argument is assumed to be a constructor (see next
	 *        argument).
	 * @param {Function|Object} [constructorOrMembers] Constructor to overwriting the base
	 *        constructor with.
	 *        If no name is provided, this argument is assumed to be an object with new prototype
	 *        members (see next argument).
	 * @param {Object} [members={}] Properties overwriting or extending those of the base.
	 * @return {Function} Constructor of the new, extended type.
	 *
	 * @throws {Error} in case a malicious function name is given or a reserved word is used.
	 */
	util.inherit = function( nameOrBase, baseOrConstructor, constructorOrMembers, members ) {
		var base = baseOrConstructor,
			constructor = constructorOrMembers;

		if( typeof nameOrBase !== 'string' ) {
			members = constructorOrMembers;
			constructor = baseOrConstructor;
			base = nameOrBase;
		}

		// allow to omit constructor since it can be inherited directly. But if given, require it as
		// second parameter for readability. If no constructor, second parameter is the prototype
		// extension object.
		if( !members ) {
			if( typeof constructor === 'function' ) {
				members = {};
			} else {
				members = constructor || {};
				constructor = false;
			}
		}

		// function we execute in our real constructor
		var NewConstructor = createFunction( constructor || base );

		// new constructor for avoiding direct use of base constructor and its potential
		// side-effects
		var NewPrototype = createFunction();
		NewPrototype.prototype = base.prototype;

		NewConstructor.prototype = extend(
			new NewPrototype(),
			members
		);

		// Set "constructor" property properly, allow explicit overwrite via member definition.
		// NOTE: in IE < 9, overwritten "constructor" properties are still set as not enumerable,
		//  so don't do this as part of the extend above.
		NewConstructor.prototype.constructor =
			members.hasOwnProperty( 'constructor' ) ? members.constructor : NewConstructor;

		return NewConstructor;
	};

	/**
	 * Throw a kind of meaningful error whenever the function should be overwritten when inherited.
	 *
	 *     @example
	 *     SomethingAbstract.prototype = {
	 *         someFunc: function( a, b ) { doSomething() },
	 *         someAbstractFunc: util.abstractFunction
	 *     };
	 *
	 * @throws {Error} when called.
	 */
	util.abstractMember = function() {
		throw new Error( 'Call to undefined abstract function' );
	};

}( util ) );
