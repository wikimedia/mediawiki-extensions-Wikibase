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

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.ui.PropertyEditTool', QUnit.newWbEnvironment( {
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
					$( '<div/>' ).append( $( '<div/>', {
						'class': 'wb-value',
						text: 'someValue'
					} ) )
				);
			}
		},
		teardown: function() {}
	} ) );


	QUnit.test( 'initial check', function( assert ) {

		var self = this;
		$.each ( this.subjects, function( i, subject ) {

			assert.ok(
				subject._toolbar instanceof wb.ui.Toolbar,
				'instantiated toolbar of property edit tool #' + i
			);

			assert.equal(
				subject._getToolbarParent().html(),
				self.nodes[i].html(),
				'placed property edit tool #' + i + ' in DOM'
			);

			assert.ok(
				subject._editableValues instanceof Array,
				'editable values of property edit tool #' + i + ' initiated correctly'
			);

		} );

		assert.equal(
			this.subjects[0].isFull(),
			true,
			'is full'
		);

		assert.equal(
			this.subjects[0].isInEditMode(),
			false,
			'is not in edit mode'
		);

		assert.equal(
			this.subjects[0].isInAddMode(),
			false,
			'is not in add mode'
		);

		assert.equal(
			this.subjects[0]._getValueElems().length,
			0,
			'has no elements with values'
		);

		assert.ok(
			this.subjects[0].getToolbar() instanceof wb.ui.Toolbar,
			'instantiated toolbar'
		);

		$.each( this.subjects, function( i, subject ) {
			subject.destroy();

			assert.equal(
				subject._editableValues,
				null,
				'destroyed editable values of property edit tool #' + i
			);

			assert.equal(
				subject._subject.children().length,
				0,
				'cleaned DOM from property edit tool #' + i
			);

		} );

	} );


	QUnit.test( 'editable values', function( assert ) {

		assert.ok(
			this.subjects[0]._initSingleValue(
				$( '<div><div class="wb-value"></div></div>' )
			) instanceof wb.ui.PropertyEditTool.EditableValue,
			'initiated editable value component'
		);

		assert.equal(
			this.subjects[0]._editableValues.length,
			1,
			'stored editable value'
		);

		assert.ok(
			this.subjects[0]._editableValues[0]._toolbar instanceof wb.ui.Toolbar,
			'instantiated toolbar for editable value'
		);

		assert.ok(
			this.subjects[0]._editableValues[0]._toolbar.editGroup instanceof wb.ui.Toolbar.EditGroup,
			'instantiated edit group for editable value toolbar'
		);

		assert.equal(
			this.subjects[0].getIndexOf( this.subjects[0]._editableValues[0] ),
			0,
			'checked index of editable value'
		);

		assert.ok(
			this.subjects[0].getValues().length === this.subjects[0].getValues( true ).length,
			'checked getValues()'
		);

		assert.ok(
			this.subjects[0].enterNewValue( '' ) instanceof wb.ui.PropertyEditTool.EditableValue,
			'instantiated editable value for entering a new value'
		);

		assert.equal(
			this.subjects[0].getValues().length,
			1,
			'one value that is not pending'
		);

		assert.equal(
			this.subjects[0].getValues( true ).length,
			2,
			'two values including pending values'
		);

		assert.equal(
			this.subjects[0].isInAddMode(),
			true,
			'is in add mode'
		);

		assert.equal(
			this.subjects[0].isInEditMode(),
			true,
			'is in edit mode'
		);

		assert.equal(
			this.subjects[0].isFull(),
			true,
			'is full'
		);

		this.subjects[0].allowsMultipleValues = false;

		assert.equal(
			this.subjects[0].isFull(),
			false,
			'is not full when using multiple values option'
		);

		assert.equal(
			this.subjects[0]._subject.children().length,
			2,
			'checked DOM'
		);

	} );


	QUnit.test( 'multiple PropertyEditTools', function( assert ) {

		assert.equal(
			this.subjects[1].isEnabled(),
			true,
			'1st edit tool is enabled'
		);

		assert.equal(
			this.subjects[1].isEnabled(),
			true,
			'2nd edit tool is enabled'
		);

		assert.equal(
			this.subjects[1]._editableValues[0].startEditing(),
			true,
			'started edit mode for 1st edit tool'
		);

		assert.equal(
			this.subjects[1]._subject.hasClass( this.subjects[1].UI_CLASS + '-ineditmode' ),
			true,
			'highlighted 1st property edit tool'
		);

		assert.equal(
			this.subjects[2]._subject.hasClass( this.subjects[2].UI_CLASS + '-ineditmode' ),
			false,
			'2nd property is not highlighted'
		);

		assert.equal(
			this.subjects[1].isEnabled(),
			true,
			'1st edit tool is still enabled'
		);

		var pet = this.subjects[2];
		pet.disable();
		assert.equal(
			this.subjects[2].isDisabled(),
			true,
			'2nd edit tool is disabled'
		);

		assert.equal(
			this.subjects[2].isEnabled(),
			false,
			'2nd edit tool is not enabled'
		);

		this.subjects[1]._editableValues[0].stopEditing();

		assert.equal(
			this.subjects[2].isEnabled(),
			true,
			'2nd edit tool is enabled'
		);

		assert.equal(
			this.subjects[1]._subject.hasClass( this.subjects[1].UI_CLASS + '-ineditmode' ),
			false,
			'removed highlight on 1st property edit tool'
		);

		assert.equal(
			this.subjects[2]._subject.hasClass( this.subjects[2].UI_CLASS + '-ineditmode' ),
			false,
			'2nd property is not highlighted'
		);

		assert.equal(
			this.subjects[1].isEnabled(),
			true,
			'1st edit tool is enabled'
		);

	} );


}( mediaWiki, wikibase, jQuery, QUnit ) );
