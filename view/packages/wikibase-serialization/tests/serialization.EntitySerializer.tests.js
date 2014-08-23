/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.EntitySerializer' );

/**
 * Object containing basic values that are supposed to just exist for testing unserializing an
 * entity. No individualization is necessary since their specific unserialization is supposed to be
 * tested by the tests of the corresponding unserializers.
 * @type {Object[]}
 */
var testBase = [
	{
		label: {
			en: 'en label'
		},
		description: {
			en: 'en description'
		},
		aliases: {
			en: ['en alias']
		},
		claims: [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' )
		]
	},
	{
		labels: {
			en: {
				language: 'en',
				value: 'en label'
			}
		},
		descriptions: {
			en: {
				language: 'en',
				value: 'en description'
			}
		},
		aliases: {
			en: [{ language: 'en', value: 'en alias' }]
		},
		claims: {
			P1: [ {
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim'
			} ]
		}
	}
];

var testCases = [
	[
		wb.datamodel.Entity.newFromMap(
			$.extend( true, {}, testBase[0],  {
				id: 'P1',
				type: 'property',
				datatype: 'string'
			} )
		),
		$.extend( true, {}, testBase[1], {
			id: 'P1',
			type: 'property',
			datatype: 'string'
		} )
	], [
		wb.datamodel.Entity.newFromMap(
			$.extend( true, {}, testBase[0],  {
				id: 'Q1',
				type: 'item',
				sitelinks: [new wb.datamodel.SiteLink( 'someSite', 'someSite title', [] )]
			} )
		),
		$.extend( true, {}, testBase[1], {
			id: 'Q1',
			type: 'item',
			sitelinks: {
				someSite: {
					site: 'someSite',
					title: 'someSite title',
					badges: []
				}
			}
		} )
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var entitySerializer = new wb.serialization.EntitySerializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			entitySerializer.serialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( jQuery, wikibase, QUnit ) );
