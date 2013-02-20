/**
 * QUnit tests for newExtension() utility function
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.newExtension', QUnit.newWbEnvironment( {
		setup: function() {},
		teardown: function() {}
	} ) );

var
	/**
	 * Takes two parameters which match the parameters of the newExtension() function.
	 * For omitting the constructor, null has to be set for it.
	 *
	 * @param base Function|null
	 * @param members Object
	 * @return Function
	 */
	newExtensionTests = function( base, members ) {
		members = $.extend( {}, members ); // clone
		var Ext;
		if( base === null ) {
			Ext = wb.utilities.newExtension( members );
		} else {
			Ext = wb.utilities.newExtension( base, members )
		}

		var allMembers = {};
		if( $.isFunction( base ) ) {
			// if we inherit a extension, it should be checked for the base's extensions members as well...
			var baseProtoMembers = $.extend( {}, base.prototype );
			delete( baseProtoMembers.constructor ); // ... the constructor property though should be ignored as usually
			$.extend( allMembers, baseProtoMembers, members );
		} else {
			allMembers = members
		}

		QUnit.assert.ok(
			$.isFunction( Ext ),
			'newExtension(' + ( base !== null ? ' base,' : '' ) + ' members ) has returned a constructor'
		);

		QUnit.assert.ok(
			$.isFunction( Ext.useWith ),
			'Added useWith() function'
		);

		QUnit.assert.deepEqual(
			Ext.prototype,
			$.extend( {}, allMembers, { constructor: Ext.prototype.constructor } ),
			'Ext.prototype has all defined members'
		);

		var newProto = {};
		Ext.useWith( newProto );
		QUnit.assert.deepEqual(
			newProto,
			allMembers,
			'extended object "o" after "newExtension(o)" has all defined extension members'
		);

		var NewConstructor = wb.utilities.inherit( Object );
		Ext.useWith( NewConstructor );
		QUnit.assert.deepEqual(
			NewConstructor.prototype,
			$.extend( {}, allMembers, { constructor: NewConstructor.prototype.constructor } ),
			'extended constructor "F" after "newExtension(F)" has all defined extension members'
		);

		return Ext;
	},

	decMembers = {
		i: 0,
		test: function() {},
		foo: 'baa'
	};

	// test with members only:
	QUnit.test( 'wb.utilities.newExtension( members )', function( assert ) {
		var Ext = newExtensionTests( null, decMembers );
	} );

	// test with members and constructor
	QUnit.test( 'wb.utilities.newExtension( base, members )', function( assert ) {
		var Ext = newExtensionTests( function() { this.foo = 'test'; }, decMembers );
		assert.equal(
			( new Ext() ).foo,
			'test',
			'Constructor was set properly by newExtension()'
		);

		var Ext2 = newExtensionTests( Ext, { foo: 'xxx', abc: 'abc' } );

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
