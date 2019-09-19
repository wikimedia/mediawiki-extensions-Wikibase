import { Store } from 'vuex';
import Application from '@/store/Application';
import EditFlow from '@/definitions/EditFlow';
import EntityRevision from '@/datamodel/EntityRevision';
import AppInformation from '@/definitions/AppInformation';
import ServiceRepositories from '@/services/ServiceRepositories';
import { createStore } from '@/store';
import { BRIDGE_INIT } from '@/store/actionTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import Term from '@/datamodel/Term';

describe( 'store/getters', () => {
	let store: Store<Application>;
	let testSet: EntityRevision;
	const services = new ServiceRepositories();
	let info: AppInformation;
	// fill repository
	beforeEach( async () => {
		testSet = {
			revisionId: 0,
			entity: {
				id: 'Q42',
				statements: {
					P31: [ {
						type: 'statement',
						id: 'opaque statement ID',
						rank: 'normal',
						mainsnak: {
							snaktype: 'value',
							property: 'P31',
							datatype: 'string',
							datavalue: {
								type: 'string',
								value: 'a string value',
							},
						},
					} ],
					P42: [ {
						type: 'statement',
						id: 'opaque statement ID',
						rank: 'normal',
						mainsnak: {
							snaktype: 'value',
							property: 'P42',
							datatype: 'string',
							datavalue: {
								type: 'monolingualtext',
								value: 'a string value',
							},
						},
					} ],
					P23: [ {
						type: 'statement',
						id: 'opaque statement ID',
						rank: 'normal',
						mainsnak: {
							snaktype: 'value',
							property: 'P23',
							datatype: 'string',
							datavalue: {
								type: 'string',
								value: 'a string value',
							},
						},
					}, {
						type: 'statement',
						id: 'other opaque statement ID',
						rank: 'normal',
						mainsnak: {
							snaktype: 'novalue',
							property: 'P23',
							datatype: 'string',
						},
					} ],
					P60: [ {
						type: 'statement',
						id: 'opaque statement ID',
						rank: 'normal',
						mainsnak: {
							snaktype: 'novalue',
							property: 'P60',
							datatype: 'string',
						},
					}, {
						type: 'statement',
						id: 'opaque statement ID',
						rank: 'normal',
						mainsnak: {
							snaktype: 'somevalue',
							property: 'P60',
							datatype: 'string',
						},
					} ],
				},
			},
		};

		services.setReadingEntityRepository( {
			async getEntity( _id: string, _revision?: number ): Promise<EntityRevision> {
				return testSet;
			},
		} );

		services.setWritingEntityRepository( {
			async saveEntity( _entity: EntityRevision ): Promise<EntityRevision> {
				throw new Error( 'These tests should not write any entities' );
			},
		} );

		services.setEntityLabelRepository( {
			async getLabel( _id ): Promise<Term> {
				return { value: 'ignore me', language: 'en' };
			},
		} );

		info = {
			editFlow: EditFlow.OVERWRITE,
			propertyId: 'P31',
			entityId: 'Q42',
		};

		store = createStore( services );
		await store.dispatch( BRIDGE_INIT, info );
	} );

	describe( 'getter aliases', () => {
		describe( 'targetValue', () => {
			it( 'returns null if the store is not ready', () => {
				const notReadyStore = createStore( services );
				expect( notReadyStore.getters.targetValue ).toBe( null );
			} );

			it( 'returns the target correct value', () => {
				expect( store.getters.targetValue ).toBe( testSet.entity.statements.P31[ 0 ].mainsnak.datavalue );
			} );
		} );

	} );

	describe( 'composed getters', () => {
		describe( 'main snak', () => {
			describe( 'null behaviour on error', () => {
				it.each( Object.keys( mainSnakGetterTypes ) )(
					'%s returns null on unknown entity', ( key: string ) => {
						expect( store.getters[
							getter(
								NS_ENTITY,
								NS_STATEMENTS,
								( mainSnakGetterTypes as any )[ key ] as string,
							)
						]( {
							entityId: 'Q9999999',
							propertyId: 'P23',
							index: 0,
						} ) ).toBeNull();
					},
				);

				it.each( Object.keys( mainSnakGetterTypes ) )(
					'%s returns null on unknown Property', ( key: string ) => {
						expect( store.getters[
							getter(
								NS_ENTITY,
								NS_STATEMENTS,
								( mainSnakGetterTypes as any )[ key ] as string,
							)
						]( {
							entityId: 'Q42',
							propertyId: 'P99999',
							index: 0,
						} ) ).toBeNull();
					},
				);

				it.each( Object.keys( mainSnakGetterTypes ) )(
					'%s returns null on unknown index on Property', ( key: string ) => {
						expect( store.getters[
							getter(
								NS_ENTITY,
								NS_STATEMENTS,
								( mainSnakGetterTypes as any )[ key ] as string,
							)
						]( {
							entityId: 'Q42',
							propertyId: 'P31',
							index: 9999,
						} ) ).toBeNull();
					},
				);

				describe( 'dataValue extended errors', () => {
					it( 'returns null if the snaktype is novalue/somevalue', () => {
						expect( store.getters[
							getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
						]( {
							entityId: 'Q42',
							propertyId: 'P60',
							index: 0,
						} ) ).toBeNull();

						expect( store.getters[
							getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
						]( {
							entityId: 'Q42',
							propertyId: 'P60',
							index: 1,
						} ) ).toBeNull();
					} );
				} );
			} );

			describe( 'resolved values', () => {
				it( 'has a snaktype', () => {
					expect( store.getters[
						getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
					]( {
						entityId: 'Q42',
						propertyId: 'P31',
						index: 0,
					} ) ).toBe( testSet.entity.statements.P31[ 0 ].mainsnak.snaktype );

					expect( store.getters[
						getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
					]( {
						entityId: 'Q42',
						propertyId: 'P23',
						index: 1,
					} ) ).toBe( testSet.entity.statements.P23[ 1 ].mainsnak.snaktype );
				} );

				it( 'has a datatype', () => {
					expect( store.getters[
						getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataType )
					]( {
						entityId: 'Q42',
						propertyId: 'P31',
						index: 0,
					} ) ).toBe( testSet.entity.statements.P31[ 0 ].mainsnak.datatype );

					expect( store.getters[
						getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataType )
					]( {
						entityId: 'Q42',
						propertyId: 'P23',
						index: 1,
					} ) ).toBe( testSet.entity.statements.P23[ 1 ].mainsnak.datatype );
				} );

				it( 'has a datavaluetype', () => {
					expect( store.getters[
						getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
					]( {
						entityId: 'Q42',
						propertyId: 'P31',
						index: 0,
					} ) ).toBe( testSet.entity.statements.P31[ 0 ].mainsnak.datavalue!.type );
				} );

				it( 'has a datavalue', () => {
					expect( store.getters[
						getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
					]( {
						entityId: 'Q42',
						propertyId: 'P31',
						index: 0,
					} ) ).toEqual( testSet.entity.statements.P31[ 0 ].mainsnak.datavalue );
				} );
			} );
		} );
	} );
} );
