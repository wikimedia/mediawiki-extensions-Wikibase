/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb, sinon, QUnit ) {
	'use strict';

	function getValueViewAndDom() {
		var valueView = {
				option: sinon.spy()
			},
			$dom = {
				valueview: sinon.spy( function () {
					return this;
				} ),
				data: function () {
					return valueView;
				}
			};

		return { vv: valueView, $dom: $dom };
	}

	QUnit.module( 'wikibase.ValueViewBuilder' );

	QUnit.test( 'initValueView returns a ValueView', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			valueView = vvAndDom.vv,
			$dom = vvAndDom.$dom;

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			{ getFormatter: function () {} }
		);

		var returnValue = valueViewBuilder.initValueView( $dom );

		assert.strictEqual( returnValue, valueView );
	} );

	QUnit.test( 'initValueView passes stores', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			expertStore = {},
			formatterFactory = { getFormatter: function () {} },
			parserStore = {};

		var valueViewBuilder = new wb.ValueViewBuilder(
			expertStore,
			formatterFactory,
			parserStore,
			null,
			null
		);

		valueViewBuilder.initValueView( $dom );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			expertStore: expertStore,
			parserStore: parserStore
		} ) );
	} );

	QUnit.test( 'initValueView passes formatters', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			htmlFormatter = {},
			plaintextFormatter = {},
			formatterFactory = {
				getFormatter: function ( dataTypeId, propertyId, outputType ) {
					return outputType === 'text/html' ? htmlFormatter : plaintextFormatter;
				}
			};

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			formatterFactory
		);

		valueViewBuilder.initValueView( $dom );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			plaintextFormatter: plaintextFormatter,
			htmlFormatter: htmlFormatter
		} ) );
	} );

	QUnit.test( 'initValueView passes language', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom;

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			{ getFormatter: function () {} },
			null,
			'de',
			null
		);

		valueViewBuilder.initValueView( $dom );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			language: 'de'
		} ) );
	} );

	QUnit.test( 'initValueView passes messageProvider', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			messageProvider = {};

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			{ getFormatter: function () {} },
			null,
			null,
			messageProvider
		);

		valueViewBuilder.initValueView( $dom );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			messageProvider: messageProvider
		} ) );
	} );

	QUnit.test( 'initValueView passes dataValue', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			dataValueType = {},
			dataValue = {
				getType: sinon.spy( function () { return dataValueType; } )
			};

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			{ getFormatter: function () {} }
		);

		valueViewBuilder.initValueView( $dom, null, dataValue );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			value: dataValue,
			dataValueType: dataValueType
		} ) );
	} );

	QUnit.test( 'initValueView infers types from dataType', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			dataTypeDataValueType = {},
			dataTypeId = {},
			dataType = {
				getId: sinon.spy( function () { return dataTypeId; } ),
				getDataValueType: sinon.spy( function () { return dataTypeDataValueType; } )
			};

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			{ getFormatter: function () {} }
		);

		valueViewBuilder.initValueView( $dom, dataType );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			dataTypeId: dataTypeId,
			dataValueType: dataTypeDataValueType
		} ) );
	} );

	QUnit.test( 'initValueView passes dataTypeId & propertyId', function ( assert ) {
		assert.expect( 1 );
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			getFormatter = sinon.spy(),
			dataType = {
				getId: function () { return 'datatype id'; },
				getDataValueType: function () { return 'datavaluetype id'; }
			};

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			{ getFormatter: getFormatter }
		);

		valueViewBuilder.initValueView( $dom, dataType, null, 'property id' );

		sinon.assert.calledWith( getFormatter, sinon.match( 'datatype id', 'property id' ) );
	} );

}( wikibase, sinon, QUnit ) );
