import { Store } from 'vuex';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import Application from '@/store/Application';
import EditFlow from '@/definitions/EditFlow';
import EntityRevision from '@/datamodel/EntityRevision';
import AppInformation from '@/definitions/AppInformation';
import ServiceRepositories from '@/services/ServiceRepositories';
import { createStore } from '@/store';
import { BRIDGE_INIT } from '@/store/actionTypes';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';

describe( 'store/actions', () => {
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

		store = createStore( services );
		await store.dispatch( BRIDGE_INIT, info );
	} );

	describe( 'application initiation', () => {
		it( 'successfully initiate on valid input data', () => {
			const successStore = createStore( services );
			return successStore.dispatch( BRIDGE_INIT, info ).then( () => {
				expect( successStore.state.applicationStatus ).toBe( ApplicationStatus.READY );
			} );
		} );
	} );

	describe( 'composed actions', () => {
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
