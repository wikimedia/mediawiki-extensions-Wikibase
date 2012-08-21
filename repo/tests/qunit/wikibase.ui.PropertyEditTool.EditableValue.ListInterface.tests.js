/**
 * QUnit tests for input interface for property edit tool which is handling lists
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';


( function () {
	module( 'wikibase.ui.PropertyEditTool.EditableValue.ListInterface', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<div><ul><li>Y</li><li>Z</li><li><!--empty--></li><li>A</li></ul></div>', { id: 'subject' } );
			this.subject = new window.wikibase.ui.PropertyEditTool.EditableValue.ListInterface( this.node );

			ok(
				this.subject._subject[0] === this.node[0],
				'validated subject'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.subject.site,
				null,
				'destroyed object'
			);

			this.node = null;
			this.subject = null;
		}
	} ) );


	test( 'basic', function() {

		ok(
			! this.subject.isEmpty(),
			'not considered empty'
		);

		equal(
			this.subject.getValue().join( '|' ),
			'Y|Z|A',
			'getValue() value equals initial value but sorted'
		);

		equal(
			this.subject.setValue( [ '3', '2', '', '1' ] ).join( '|' ),
			'3|2|1',
			'set new value, normalized it'
		);

	} );

	test( 'valueCompare()', function() {

		ok(
			this.subject.valueCompare( [ 'a', 'b' ], [ 'a', 'b' ] ),
			'simple strings, different order, equal'
		);

		ok(
			! this.subject.valueCompare( [ 'a', 'b' ], [ 'a', 'b', 'c' ] ),
			'more values in first argument, not equal'
		);

		ok(
			! this.subject.valueCompare( [ 'a', 'b', 'c' ], [ 'a', 'b' ] ),
			'more values in second argument, not equal'
		);

		ok(
			! this.subject.valueCompare( [ 'a' ] ),
			'value given, not empty'
		);

		ok(
			this.subject.valueCompare( [] ),
			'empty array considered empty'
		);

		ok(
			this.subject.valueCompare( [ '', '' ] ),
			'array with empty strings, considered empty'
		);

	} );


}() );
