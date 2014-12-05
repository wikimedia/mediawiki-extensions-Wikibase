/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( $, wb, QUnit ) {
'use strict';

QUnit.module( 'jquery.wikibase.entityview' );

QUnit.test( 'Direct initialization fails', function( assert ) {
	assert.throws(
		function() {
			$( '<div/>' ).entityview( $.extend( {
				entityStore: 'I am an EntityStore',
				entityChangersFactory: {
					getAliasesChanger: function() { return 'I am an AliasesChanger'; },
					getDescriptionsChanger: function() { return 'I am a DescriptionsChanger'; },
					getLabelsChanger: function() { return 'I am a LabelsChanger'; }
				},
				api: 'I am an Api',
				valueViewBuilder: 'I am a valueview builder',
				value: new wb.datamodel.Property( 'P1', 'someDataType' )
			} ) );
		},
		'Throwing error when trying to initialize widget directly.'
	);
} );

}( jQuery, wikibase, QUnit ) );
