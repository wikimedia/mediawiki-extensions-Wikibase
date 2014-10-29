/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.EntityDeserializer' );

/**
 * @constructor
 * @extends {wikibase.serialization.Deserializer}
 */
var MockEntityDeserializer = util.inherit(
	'WbMockEntityDeserializer',
	wb.serialization.Deserializer,
{
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {MockEntity}
	 */
	deserialize: function( serialization ) {
		if( serialization.type !== wb.serialization.tests.MockEntity.TYPE ) {
			throw new Error( 'Serialization does not resolve to a MockEntity' );
		}

		var fingerprintDeserializer = new wb.serialization.FingerprintDeserializer();

		return new wb.serialization.tests.MockEntity(
			serialization.id,
			fingerprintDeserializer.deserialize( serialization )
		);
	}
} );

var defaults = [
	{
		fingerprint: {
			labels: { en: { language: 'en', value: 'label' } },
			descriptions: { en: { language: 'en', value: 'description' } },
			aliases: { en: [{ language: 'en', value: 'alias' }] }
		},
		statementGroupSet: {
			P1: [ {
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'statement',
				rank: 'normal'
			} ]
		}
	}, {
		fingerprint: new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'label' ) } ),
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'description' ) } ),
			new wb.datamodel.MultiTermMap( { en: new wb.datamodel.MultiTerm( 'en', ['alias'] ) } )
		),
		statementGroupSet: new wb.datamodel.StatementGroupSet( [
			new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim(
						new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1'
					)
				)
			] ) )
		] )
	}
];

var testSets = [
	[
		$.extend( true, {}, defaults[0].fingerprint, {
			id: 'P1',
			type: 'property',
			datatype: 'string',
			claims: defaults[0].statementGroupSet
		} ),
		new wb.datamodel.Property(
			'P1',
			'string',
			defaults[1].fingerprint,
			defaults[1].statementGroupSet
		)
	], [
		$.extend( true, {}, defaults[0].fingerprint, {
			id: 'Q1',
			type: 'item',
			claims: defaults[0].statementGroupSet,
			sitelinks: {
				someSite: {
					site: 'someSite',
					title: 'page',
					badges: []
				}
			}
		} ),
		new wb.datamodel.Item(
			'Q1',
			defaults[1].fingerprint,
			defaults[1].statementGroupSet,
			new wb.datamodel.SiteLinkSet( [new wb.datamodel.SiteLink( 'someSite', 'page' )] )
		)
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var entityDeserializer = new wb.serialization.EntityDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			entityDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

QUnit.test( 'registerStrategy()', function( assert ) {
	var entityDeserializer = new wb.serialization.EntityDeserializer();

	var mockEntitySerialization = $.extend( true, {
		id: 'i am an id',
		type: 'mock'
	}, defaults[0].fingerprint );

	assert.throws(
		function() {
			entityDeserializer.deserialize( mockEntitySerialization );
		},
		'Throwing an error when trying to deserialize an Entity no Deserializer is registered for.'
	);

	entityDeserializer.registerStrategy(
		new MockEntityDeserializer(),
		wb.serialization.tests.MockEntity.TYPE
	);

	var mockEntity = entityDeserializer.deserialize( mockEntitySerialization );

	assert.ok(
		mockEntity instanceof wb.serialization.tests.MockEntity,
		'Deserialized Entity after registering a proper Deserializer.'
	);
} );

}( jQuery, wikibase, QUnit ) );
