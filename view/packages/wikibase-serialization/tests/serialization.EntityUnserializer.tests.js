/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.EntityUnserializer' );

/**
 * Object containing basic values that are supposed to just exist for testing unserializing an
 * entity. No individualization is necessary since their specific unserialization is supposed to be
 * tested by the tests of the corresponding unserializers.
 * @type {Object[]}
 */
var testBase = [
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
			en: ['en alias']
		},
		claims: {
			P1: [ {
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim',
				rank: 'normal'
			} ]
		}
	}, {
		labels: {
			en: 'en label'
		},
		descriptions: {
			en: 'en description'
		},
		aliases: {
			en: ['en alias']
		},
		claims: [ {
			id: 'Q1$1',
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			type: 'claim',
			rank: 'normal'
		} ]
	}
];

var testCases = [
	[
		$.extend( true, {}, testBase[0], {
			id: 'P1',
			type: 'property',
			datatype: 'string'
		} ),
		wb.datamodel.Entity.newFromMap(
			$.extend( true, {}, testBase[1],  {
				id: 'P1',
				type: 'property'
			} )
		)
	], [
		$.extend( true, {}, testBase[0], {
			id: 'Q1',
			type: 'item',
			sitelinks: {
				someSite: {
					site: 'someSite',
					title: 'someSite title',
					badges: []
				}
			}
		} ),
		wb.datamodel.Entity.newFromMap(
			$.extend( true, {}, testBase[1],  {
				id: 'Q1',
				type: 'item',
				sitelinks: [ {
					site: 'someSite',
					title: 'someSite title',
					badges: []
				} ]
			} )
		)
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var entityUnserializer = new wb.serialization.EntityUnserializer();

	for( var i = 0; i < testCases.length; i++ ) {
		var entity = entityUnserializer.unserialize( testCases[i][0] ),
			expectedEntity = testCases[i][1];

		// TODO: Use equals() as soon as it is implemented in wb.datamodel.Entity
		assert.ok(
			entity.getId() === expectedEntity.getId()
			&& entity.getType() === expectedEntity.getType(),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( jQuery, wikibase, QUnit ) );
