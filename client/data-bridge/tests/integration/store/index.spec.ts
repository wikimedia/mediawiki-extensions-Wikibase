import { Store } from 'vuex';
import Application from '@/store/Application';
import EditFlow from '@/definitions/EditFlow';
import EntityRevision from '@/datamodel/EntityRevision';
import AppInformation from '@/definitions/AppInformation';
import ServiceRepositories from '@/services/ServiceRepositories';
import { createStore } from '@/store';
import { BRIDGE_INIT } from '@/store/actionTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';

describe( 'store', () => {
	let testSet: EntityRevision;
	const services = new ServiceRepositories();
	let info: AppInformation;
	// fill repository
	beforeEach( () => {
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

		services.setEntityRepository( {
			async getEntity( _id: string, _revision?: number ): Promise<EntityRevision> {
				return testSet;
			},
		} );

		info = {
			editFlow: EditFlow.OVERWRITE,
			propertyId: 'P31',
			entityId: 'Q42',
		};
	} );

	it( 'returns correct target value', () => {
		const store = createStore( services );

		expect( store.getters.targetValue ).toBe( null );

		return store.dispatch( BRIDGE_INIT, info ).then( () => {
			expect( store.getters.targetValue ).toBe( 'a string value' );
		} );
	} );

	describe( 'composed getters', () => {
		let store: Store<Application>;
		beforeAll( async () => {
			services.setEntityRepository( {
				async getEntity( _id: string, _revision?: number ): Promise<EntityRevision> {
					return testSet;
				},
			} );

			store = createStore( services );
			await store.dispatch( BRIDGE_INIT, info );
		} );

		describe( 'snaktype', () => {
			it( 'has a snaktype', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 0,
				} ) ).toBe( testSet.entity.statements.P31[ 0 ].mainsnak.snaktype );

				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
				]( {
					entityId: 'Q42',
					propertyId: 'P23',
					index: 1,
				} ) ).toBe( testSet.entity.statements.P23[ 1 ].mainsnak.snaktype );
			} );

			it( 'returns null on unknown entity', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
				]( {
					entityId: 'Q9999999',
					propertyId: 'P23',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
				]( {
					entityId: 'Q42',
					propertyId: 'P99999',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown index on Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 42,
				} ) ).toBeNull();
			} );
		} );

		describe( 'datatype', () => {
			it( 'has a datatype', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataType )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 0,
				} ) ).toBe( testSet.entity.statements.P31[ 0 ].mainsnak.datatype );

				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataType )
				]( {
					entityId: 'Q42',
					propertyId: 'P23',
					index: 1,
				} ) ).toBe( testSet.entity.statements.P23[ 1 ].mainsnak.datatype );
			} );

			it( 'returns null on unknown entity', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataType )
				]( {
					entityId: 'Q9999999',
					propertyId: 'P23',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataType )
				]( {
					entityId: 'Q42',
					propertyId: 'P99999',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown index on Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataType )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 42,
				} ) ).toBeNull();
			} );
		} );

		describe( 'datavaluetype', () => {
			it( 'has a datavaluetype', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 0,
				} ) ).toBe( testSet.entity.statements.P31[ 0 ].mainsnak.datavalue!.type );
			} );

			it( 'returns null on unknown entity', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
				]( {
					entityId: 'Q9999999',
					propertyId: 'P23',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
				]( {
					entityId: 'Q42',
					propertyId: 'P99999',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown index on Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 42,
				} ) ).toBeNull();
			} );

			it( 'returns null if there is no datavalue', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
				]( {
					entityId: 'Q42',
					propertyId: 'P23',
					index: 42,
				} ) ).toBeNull();
			} );
		} );

		describe( 'datavalue', () => {
			it( 'has a datavalue', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 0,
				} ) ).toEqual( testSet.entity.statements.P31[ 0 ].mainsnak.datavalue );
			} );

			it( 'returns null on unknown entity', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
				]( {
					entityId: 'Q9999999',
					propertyId: 'P23',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
				]( {
					entityId: 'Q42',
					propertyId: 'P99999',
					index: 0,
				} ) ).toBeNull();
			} );

			it( 'returns null on unknown index on Property', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
				]( {
					entityId: 'Q42',
					propertyId: 'P31',
					index: 42,
				} ) ).toBeNull();
			} );

			it( 'returns null if there is no datavalue', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
				]( {
					entityId: 'Q42',
					propertyId: 'P23',
					index: 42,
				} ) ).toBeNull();
			} );

			it( 'returns null if the snaktype is novalue/somevalue', () => {
				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
				]( {
					entityId: 'Q42',
					propertyId: 'P60',
					index: 0,
				} ) ).toBeNull();

				expect( store.getters[
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
				]( {
					entityId: 'Q42',
					propertyId: 'P60',
					index: 1,
				} ) ).toBeNull();
			} );
		} );
	} );

	describe( 'composed actions', () => {
		let store: Store<Application>;

		beforeEach( async () => {
			store = createStore( services );
			services.setEntityRepository( {
				async getEntity( _id: string, _revision?: number ): Promise<EntityRevision> {
					return testSet;
				},
			} );
			await store.dispatch( BRIDGE_INIT, info );
		} );

		describe( 'setStringDataValue', () => {
			it( 'rejects on unknown Entity', async () => {
				expect.assertions( 1 );
				await expect( store.dispatch(
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakActionTypes.setStringDataValue ),
					{
						path: {
							entityId: 'Q3333333',
							propertyId: 'P23',
							index: 0,
						},
						value: {
							type: 'string',
							value: 'passed teststring',
						},
					},
				) ).rejects.toStrictEqual( Error( SnakActionErrors.NO_SNAK_FOUND ) );
			} );

			it( 'rejects on unknown Property', async () => {
				expect.assertions( 1 );
				await expect( store.dispatch(
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakActionTypes.setStringDataValue ),
					{
						path: {
							entityId: 'Q42',
							propertyId: 'P99999',
							index: 0,
						},
						value: {
							type: 'string',
							value: 'passed teststring',
						},
					},
				) ).rejects.toStrictEqual( Error( SnakActionErrors.NO_SNAK_FOUND ) );
			} );

			it( 'rejects on unknown index on Property', async () => {
				expect.assertions( 1 );
				await expect( store.dispatch(
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakActionTypes.setStringDataValue ),
					{
						path: {
							entityId: 'Q42',
							propertyId: 'P31',
							index: 42,
						},
						value: {
							type: 'string',
							value: 'passed teststring',
						},
					},
				) ).rejects.toStrictEqual( Error( SnakActionErrors.NO_SNAK_FOUND ) );
			} );

			it( 'rejects on non string value data types', async () => {
				expect.assertions( 1 );
				await expect( store.dispatch(
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakActionTypes.setStringDataValue ),
					{
						path: {
							entityId: 'Q42',
							propertyId: 'P42',
							index: 0,
						},
						value: {
							type: 'url',
							value: 'passed teststring',
						},
					},
				) ).rejects.toStrictEqual( Error( SnakActionErrors.WRONG_PAYLOAD_TYPE ) );
			} );

			it( 'rejects on non string data value', async () => {
				expect.assertions( 1 );
				await expect( store.dispatch(
					namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakActionTypes.setStringDataValue ),
					{
						path: {
							entityId: 'Q42',
							propertyId: 'P42',
							index: 0,
						},
						value: {
							type: 'string',
							value: 23,
						},
					},
				) ).rejects.toStrictEqual( Error( SnakActionErrors.WRONG_PAYLOAD_VALUE_TYPE ) );
			} );

			describe( 'resolves on valid string data values', () => {

				it( 'sets the new data value', () => {
					const value = {
						type: 'string',
						value: 'passed teststring',
					};

					return store.dispatch(
						namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakActionTypes.setStringDataValue ),
						{
							path: {
								entityId: 'Q42',
								propertyId: 'P31',
								index: 0,
							},
							value,
						},
					).then( () => {
						expect(
							( store.state as any ).entity.statements.Q42.P31[ 0 ].mainsnak.datavalue,
						).toBe( value );
					} );
				} );

				it( 'sets the snak type to value', () => {
					const value = {
						type: 'string',
						value: 'passed teststring',
					};

					return store.dispatch(
						namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakActionTypes.setStringDataValue ),
						{
							path: {
								entityId: 'Q42',
								propertyId: 'P60',
								index: 0,
							},
							value,
						},
					).then( () => {
						expect(
							( store.state as any ).entity.statements.Q42.P60[ 0 ].mainsnak.snaktype,
						).toBe( 'value' );
					} );
				} );
			} );
		} );
	} );
} );
