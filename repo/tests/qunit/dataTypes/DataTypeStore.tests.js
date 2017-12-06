/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( QUnit, dataTypes ) {
	'use strict';

	QUnit.module( 'wikibase.dataTypes.DataTypeStore' );

	QUnit.test( 'Test initializing a DataType object', function ( assert ) {
		var dataTypeStore = new dataTypes.DataTypeStore(),
			testDataType = new dataTypes.DataType( 'foo', 'fooDataValueType' ),
			testDataTypeId = testDataType.getId();

		dataTypeStore.registerDataType( testDataType );

		assert.ok(
			dataTypeStore.hasDataType( testDataTypeId ),
			'hasDataType: Data type "' + testDataTypeId + '" is available after registering it'
		);

		assert.ok(
			testDataType === dataTypeStore.getDataType( testDataTypeId ),
			'getDataType: returns exact same instance of the data type which was registered before'
		);
	} );

}( QUnit, wikibase.dataTypes ) );
