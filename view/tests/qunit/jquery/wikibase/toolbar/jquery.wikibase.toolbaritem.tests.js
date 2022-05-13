/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var createTestItem = function ( options ) {
		return $( '<span>' )
			.text( 'text' )
			.addClass( 'test_toolbaritem' )
			.toolbaritem( options || {} );
	};

	QUnit.module( 'jquery.wikibase.toolbaritem', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_toolbaritem' ).each( function () {
				var $item = $( this ).data( 'toolbaritem' ),
					item = $item.data( 'toolbaritem' );

				if ( item ) {
					item.destroy();
				}

				$item.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $item = createTestItem(),
			item = $item.data( 'toolbaritem' );

		assert.true(
			item instanceof $.wikibase.toolbaritem,
			'Instantiated widget.'
		);

		item.destroy();

		assert.strictEqual(
			$item.data( 'toolbaritem' ),
			undefined,
			'Destroyed widget.'
		);
	} );

}() );
