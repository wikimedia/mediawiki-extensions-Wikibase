/**
 * @license GPL-2.0-or-later
 */
( function ( sinon, QUnit ) {
	const EntityLoadedHookEntityRepository = require(
		'../../../../resources/wikibase/termbox/EntityLoadedHookEntityRepository.js' );
	QUnit.module( 'wikibase.termbox.EntityLoadedHookEntityRepository' );
	QUnit.test( "returns the entityLoaded hook's entity", function ( assert ) {
		const expectedEntity = {
			id: 'Q42',
			labels: {
				de: { language: 'de', value: 'Kartoffel' },
				en: { language: 'en', value: 'potato' }
			},
			descriptions: {},
			aliases: {}
		};

		const hookStub = {
			add: sinon.stub().yields( expectedEntity )
		};

		new EntityLoadedHookEntityRepository( hookStub )
			.getFingerprintableEntity()
			.then(
				( entity ) => {
					assert.deepEqual( entity, expectedEntity );

					// test that the entity object is cloned
					assert.notStrictEqual( entity, expectedEntity );
				}
			);
		assert.true( hookStub.add.calledOnce );
	} );
}( sinon, QUnit ) );
