/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, sinon, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.ValueViewBuilder' );

	QUnit.test( 'initValueView returns a ValueView', function( assert ) {
		var vvAndDom = getValueViewAndDom(),
			valueView = vvAndDom.vv,
			$dom = vvAndDom.$dom;

		var valueViewBuilder = new wb.ValueViewBuilder();

		var returnValue = valueViewBuilder.initValueView( $dom );

		assert.strictEqual( returnValue, valueView );
	} );

	QUnit.test( 'initValueView passes stores', function( assert ) {
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			expertStore = {},
			formatterStore = {},
			parserStore = {};

		var valueViewBuilder = new wb.ValueViewBuilder(
			expertStore,
			formatterStore,
			parserStore,
			null
		);

		valueViewBuilder.initValueView( $dom );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			expertStore: expertStore,
			formatterStore: formatterStore,
			parserStore: parserStore
		} ) );
	} );

	QUnit.test( 'initValueView passes mw', function( assert ) {
		var vvAndDom = getValueViewAndDom(),
			valueView = vvAndDom.vv,
			$dom = vvAndDom.$dom,
			mw = {};

		var valueViewBuilder = new wb.ValueViewBuilder(
			null,
			null,
			null,
			mw
		);

		valueViewBuilder.initValueView( $dom );

		sinon.assert.calledWith( valueView.option, sinon.match( {
			mediaWiki: mw
		} ) );
	} );

	QUnit.test( 'initValueView passes dataValue', function( assert ) {
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			dataValueType = {},
			dataValue = {
				getType: sinon.spy( function() { return dataValueType; } )
			};

		var valueViewBuilder = new wb.ValueViewBuilder();

		valueViewBuilder.initValueView( $dom, null, dataValue );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			value: dataValue,
			dataValueType: dataValueType
		} ) );
	} );

	QUnit.test( 'initValueView infers types from dataType', function( assert ) {
		var vvAndDom = getValueViewAndDom(),
			$dom = vvAndDom.$dom,
			dataTypeDataValueType = {},
			dataTypeId = {},
			dataType = {
				getId: sinon.spy( function() { return dataTypeId; } ),
				getDataValueType: sinon.spy( function() { return dataTypeDataValueType; } )
			};

		var valueViewBuilder = new wb.ValueViewBuilder();

		valueViewBuilder.initValueView( $dom, dataType );

		sinon.assert.calledWith( $dom.valueview, sinon.match( {
			dataTypeId: dataTypeId,
			dataValueType: dataTypeDataValueType
		} ) );
	} );

	function getValueViewAndDom() {
		var valueView = {
				option: sinon.spy()
			},
			$dom = {
				valueview: sinon.spy( function() {
					return this;
				} ),
				data: function() {
					return valueView;
				}
			};

		return { vv: valueView, $dom: $dom };
	}

}( wikibase, sinon, QUnit ) );
