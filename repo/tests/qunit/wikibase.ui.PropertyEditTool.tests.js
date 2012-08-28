/**
 * QUnit tests for property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( $, wb ) {
	'use strict';

	module( 'wikibase.ui.PropertyEditTool', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.nodes = [
				$( '<div/>' )
			];
			this.subjects = [
				new wb.ui.PropertyEditTool( this.nodes[0] )
			];
			for ( var i = 1; i <= 2; i += 1 ) {
				this.nodes.push( $( '<div/>' ) );
				this.subjects.push( new wb.ui.PropertyEditTool( this.nodes[i] ) );
				this.subjects[i].allowsMultipleValues = false;
				this.subjects[i].init( this.nodes[i] );
				this.subjects[i]._initSingleValue(
					$( '<div/>', {
						text: 'someValue'
					} )
				);
			}

			var self = this;
			$.each ( this.subjects, function( i, subject ) {

				ok(
					subject._toolbar instanceof wb.ui.Toolbar,
					'instantiated toolbar of property edit tool #' + i
				);

				equal(
					subject._getToolbarParent().html(),
					self.nodes[i].html(),
					'placed property edit tool #' + i + ' in DOM'
				);

				ok(
					subject._editableValues instanceof Array,
					'editable values of property edit tool #' + i + ' initiated correctly'
				);

			} );

		},
		teardown: function() {

			$.each( this.subjects, function( i, subject ) {
				subject.destroy();

				equal(
					subject._editableValues,
					null,
					'destroyed editable values of property edit tool #' + i
				);

				equal(
					subject._subject.children().length,
					0,
					'cleaned DOM from property edit tool #' + i
				);

			} );

			this.nodes = null;
			this.subjects = null;
		}

	} ) );


	test( 'initial check', function() {

		equal(
			this.subjects[0].isFull(),
			true,
			'is full'
		);

		equal(
			this.subjects[0].isInEditMode(),
			false,
			'is not in edit mode'
		);

		equal(
			this.subjects[0].isInAddMode(),
			false,
			'is not in add mode'
		);

		equal(
			this.subjects[0]._getValueElems().length,
			0,
			'has no elements with values'
		);

		ok(
			this.subjects[0].getToolbar() instanceof wb.ui.Toolbar,
			'instantiated toolbar'
		);

	} );


	test( 'editable values', function() {

		ok(
			this.subjects[0]._initSingleValue( $( '<div/>' ) ) instanceof wb.ui.PropertyEditTool.EditableValue,
			'initiated editable value component'
		);

		equal(
			this.subjects[0]._editableValues.length,
			1,
			'stored editable value'
		);

		ok(
			this.subjects[0]._editableValues[0]._toolbar instanceof wb.ui.Toolbar,
			'instantiated toolbar for editable value'
		);

		ok(
			this.subjects[0]._editableValues[0]._toolbar.editGroup instanceof wb.ui.Toolbar.EditGroup,
			'instantiated edit group for editable value toolbar'
		);

		equal(
			this.subjects[0].getIndexOf( this.subjects[0]._editableValues[0] ),
			0,
			'checked index of editable value'
		);

		ok(
			this.subjects[0].getValues().length === this.subjects[0].getValues( true ).length,
			'checked getValues()'
		);

		ok(
			this.subjects[0].enterNewValue( '' ) instanceof wb.ui.PropertyEditTool.EditableValue,
			'instantiated editable value for entering a new value'
		);

		equal(
			this.subjects[0].getValues().length,
			1,
			'one value that is not pending'
		);

		equal(
			this.subjects[0].getValues( true ).length,
			2,
			'two values including pending values'
		);

		equal(
			this.subjects[0].isInAddMode(),
			true,
			'is in add mode'
		);

		equal(
			this.subjects[0].isInEditMode(),
			true,
			'is in edit mode'
		);

		equal(
			this.subjects[0].isFull(),
			true,
			'is full'
		);

		this.subjects[0].allowsMultipleValues = false;

		equal(
			this.subjects[0].isFull(),
			false,
			'is not full'
		);

		equal(
			this.subjects[0]._subject.children().length,
			2,
			'checked DOM'
		);

	} );


	test( 'multiple PropertyEditTools', function() {

		equal(
			this.subjects[1].isEnabled(),
			true,
			'1st edit tool is enabled'
		);

		equal(
			this.subjects[1].isEnabled(),
			true,
			'2nd edit tool is enabled'
		);

		equal(
			this.subjects[1]._editableValues[0].startEditing(),
			true,
			'started edit mode for 1st edit tool'
		);

		equal(
			this.subjects[1]._subject.hasClass( this.subjects[1].UI_CLASS + '-ineditmode' ),
			true,
			'highlighted 1st property edit tool'
		);

		equal(
			this.subjects[2]._subject.hasClass( this.subjects[2].UI_CLASS + '-ineditmode' ),
			false,
			'2nd property is not highlighted'
		);

		equal(
			this.subjects[1].isEnabled(),
			true,
			'1st edit tool is still enabled'
		);

		equal(
			this.subjects[2].isDisabled(),
			true,
			'2nd edit tool is disabled'
		);

		equal(
			this.subjects[2].isEnabled(),
			false,
			'2nd edit tool is not enabled'
		);

		this.subjects[1]._editableValues[0].stopEditing();

		equal(
			this.subjects[2].isEnabled(),
			true,
			'2nd edit tool is enabled'
		);

		equal(
			this.subjects[1]._subject.hasClass( this.subjects[1].UI_CLASS + '-ineditmode' ),
			false,
			'removed highlight on 1st property edit tool'
		);

		equal(
			this.subjects[2]._subject.hasClass( this.subjects[2].UI_CLASS + '-ineditmode' ),
			false,
			'2nd property is not highlighted'
		);

		equal(
			this.subjects[1].isEnabled(),
			true,
			'1st edit tool is enabled'
		);

	} );


}( jQuery, wikibase ) );
