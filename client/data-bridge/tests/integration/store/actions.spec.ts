import { Store } from 'vuex';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import Application, { InitializedApplicationState } from '@/store/Application';
import EditFlow from '@/definitions/EditFlow';
import EntityRevision from '@/datamodel/EntityRevision';
import AppInformation from '@/definitions/AppInformation';
import ServiceContainer from '@/services/ServiceContainer';
import { createStore } from '@/store';
import {
	BRIDGE_INIT,
	BRIDGE_SAVE,
	BRIDGE_SET_TARGET_VALUE,
} from '@/store/actionTypes';
import { ENTITY_SAVE } from '@/store/entity/actionTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';
import { action } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import Term from '@/datamodel/Term';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import { PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import { SNAK_SET_STRING_DATA_VALUE } from '@/store/statements/snaks/actionTypes';
import { MainSnakPath } from '@/store/statements/MainSnakPath';

describe( 'store/actions', () => {
	let store: Store<Application>;
	let testSet: EntityRevision;
	let labelTerm: Term;
	const services = new ServiceContainer();
	const client = { usePublish: false };
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

		labelTerm = { language: 'en', value: 'potato' };

		services.set( 'readingEntityRepository', {
			async getEntity( _id: string, _revision?: number ): Promise<EntityRevision> {
				return testSet;
			},
		} );

		services.set( 'writingEntityRepository', {
			async saveEntity( _entity: EntityRevision ): Promise<EntityRevision> {
				throw new Error( 'These tests should not write any entities' );
			},
		} );

		services.set( 'entityLabelRepository', {
			async getLabel( _id: string ): Promise<Term> {
				return labelTerm;
			},
		} );

		services.set( 'wikibaseRepoConfigRepository', {
			async getRepoConfiguration(): Promise<WikibaseRepoConfiguration> {
				return {
					dataTypeLimits: {
						string: {
							maxLength: 200,
						},
					},
				};
			},
		} );

		services.set( 'propertyDatatypeRepository', {
			getDataType: jest.fn().mockResolvedValue( 'string' ),
		} );

		services.set( 'tracker', {
			trackPropertyDatatype: jest.fn(),
		} );

		services.set( 'editAuthorizationChecker', {
			canUseBridgeForItemAndPage: () => Promise.resolve( [] ),
		} );

		info = {
			pageTitle: 'Client_page',
			editFlow: EditFlow.OVERWRITE,
			propertyId: 'P31',
			entityId: 'Q42',
			entityTitle: 'Q42',
			client,
			originalHref: 'https://example.com/index.php?title=Item:Q42&uselang=en#P31',
		};

		store = createStore( services );
		await store.dispatch( BRIDGE_INIT, info );
	} );

	describe( 'application initiation', () => {
		it( 'successfully initiate on valid input data', () => {
			const successStore = createStore( services );
			return successStore.dispatch( BRIDGE_INIT, info ).then( () => {
				expect( successStore.state.applicationStatus ).toBe( ApplicationStatus.READY );
				expect( successStore.state.targetLabel ).toBe( labelTerm );
				expect( successStore.state.originalStatement ).not.toBe(
					testSet.entity.statements[ info.propertyId ][ 0 ],
				);
				expect( successStore.state.originalStatement ).toStrictEqual(
					testSet.entity.statements[ info.propertyId ][ 0 ],
				);
				expect( successStore.state.originalHref ).toStrictEqual(
					info.originalHref,
				);
				expect( successStore.state.pageTitle ).toBe( info.pageTitle );
			} );
		} );

		describe( 'transitions to error state', () => {
			it( 'switches into error state if the statement not existing on the entity', () => {
				const errorStore = createStore( services );
				const misleadingInfo = {
					editFlow: EditFlow.OVERWRITE,
					propertyId: 'P2312',
					entityId: 'Q42',
					entityTitle: 'Q42',
					client,
				};

				return errorStore.dispatch( BRIDGE_INIT, misleadingInfo ).then( () => {
					expect( errorStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
				} );
			} );

			it( 'switches into error state if the statement is ambiguous', () => {
				const errorStore = createStore( services );
				const misleadingInfo = {
					editFlow: EditFlow.OVERWRITE,
					propertyId: 'P60',
					entityId: 'Q42',
					entityTitle: 'Q42',
					client,
				};
				return errorStore.dispatch( BRIDGE_INIT, misleadingInfo ).then( () => {
					expect( errorStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
				} );
			} );

			it( 'switches into error state if has not a `value` as snak type', () => {
				const errorStore = createStore( services );
				const misleadingInfo = {
					editFlow: EditFlow.OVERWRITE,
					propertyId: 'P23',
					entityId: 'Q42',
					entityTitle: 'Q42',
					client,
				};
				return errorStore.dispatch( BRIDGE_INIT, misleadingInfo ).then( () => {
					expect( errorStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
				} );
			} );

			it( 'switch into error state if is not a string data value type', () => {
				const errorStore = createStore( services );
				const misleadingInfo = {
					editFlow: EditFlow.OVERWRITE,
					propertyId: 'P42',
					entityId: 'Q42',
					entityTitle: 'Q42',
					client,
				};
				return errorStore.dispatch( BRIDGE_INIT, misleadingInfo ).then( () => {
					expect( errorStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
				} );
			} );

			it( 'switch into error state if there are permission errors', () => {
				services.set( 'editAuthorizationChecker', {
					canUseBridgeForItemAndPage: () => Promise.resolve( [
						{
							type: PageNotEditable.ITEM_SEMI_PROTECTED,
							info: { right: 'editsemiprotected' },
						},
					] ),
				} );

				const permissionProblemStore = createStore( services );
				const misleadingInfo = {
					editFlow: EditFlow.OVERWRITE,
					propertyId: 'P42',
					entityId: 'Q42',
					entityTitle: 'Q42',
					client,
				};
				return permissionProblemStore.dispatch( BRIDGE_INIT, misleadingInfo ).then( () => {
					expect( permissionProblemStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
				} );
			} );
		} );
	} );

	describe( ENTITY_SAVE, () => {
		it( 'rejects if the request fails', async () => {
			const rejectError = new Error( 'no' );
			const resolver = jest.fn().mockRejectedValue( rejectError );

			services.set( 'writingEntityRepository', {
				saveEntity: resolver as any,
			} );

			store = createStore( services );
			await store.dispatch( BRIDGE_INIT, info );
			await expect(
				store.dispatch(
					action( NS_ENTITY, ENTITY_SAVE ),
				),
			).rejects.toBe( rejectError );

			expect( resolver ).toHaveBeenCalledWith( testSet );
		} );

		it( 'stores the responded entity, if the request succeeded', async () => {
			const response = {
				revisionId: 1,
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
					},
				},
			};

			const resolver = jest.fn().mockResolvedValue( response );

			services.set( 'writingEntityRepository', {
				saveEntity: resolver as any,
			} );

			store = createStore( services );
			await store.dispatch( BRIDGE_INIT, info );
			await store.dispatch( action( NS_ENTITY, ENTITY_SAVE ) );

			expect( resolver ).toHaveBeenCalledWith( testSet );

			const state = store.state as InitializedApplicationState;
			expect( state.statements ).toEqual( { Q42: response.entity.statements } );
			expect( state.entity.id ).toBe( response.entity.id );
			expect( state.entity.baseRevision ).toBe( response.revisionId );
		} );
	} );

	describe( 'alias actions', () => {
		describe( BRIDGE_SET_TARGET_VALUE, () => {
			it( 'rejects if the store is not ready and switches to error state', async () => {
				const notReadyStore = createStore( services );
				await expect( notReadyStore.dispatch(
					BRIDGE_SET_TARGET_VALUE,
					{ type: 'string', dataValue: 'passing string' },
				) ).rejects.toBeDefined();
				expect( notReadyStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
			} );

			it( 'sets the new data value', () => {
				const dataValue = { type: 'string', value: 'Töften as passing string' };
				return store.dispatch(
					BRIDGE_SET_TARGET_VALUE,
					dataValue,
				).then( () => {
					const state = ( store.state as InitializedApplicationState );
					expect( state.statements.Q42.P31[ 0 ].mainsnak.datavalue ).toBe( dataValue );
				} );
			} );
		} );

		describe( BRIDGE_SAVE, () => {
			it( 'rejects if the store is not ready and switches to error state', async () => {
				const notReadyStore = createStore( services );
				await expect( notReadyStore.dispatch(
					BRIDGE_SAVE,
					{ type: 'string', dataValue: 'passing string' },
				) ).rejects.toBeDefined();
				expect( notReadyStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
			} );

			it( 'rejects and switch to error if the request fails', async () => {
				const rejectError = new Error( 'no' );
				const resolver = jest.fn().mockRejectedValue( rejectError );

				services.set( 'writingEntityRepository', {
					saveEntity: resolver as any,
				} );

				store = createStore( services );
				await store.dispatch( BRIDGE_INIT, info );
				await expect(
					store.dispatch( BRIDGE_SAVE ),
				).rejects.toBe( rejectError );

				expect( resolver ).toHaveBeenCalledWith( testSet );
				expect( store.state.applicationErrors.length ).toBeGreaterThan( 0 );
			} );

			it( 'stores the responded entity, if the request succeeded', async () => {
				const response = {
					revisionId: 1,
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
						},
					},
				};

				const resolver = jest.fn().mockResolvedValue( response );

				services.set( 'writingEntityRepository', {
					saveEntity: resolver as any,
				} );

				store = createStore( services );

				await store.dispatch( BRIDGE_INIT, info );
				await store.dispatch( BRIDGE_SAVE );

				expect( resolver ).toHaveBeenCalledWith( testSet );

				const state = ( store.state as InitializedApplicationState );
				expect( state.statements.Q42 ).toBe( response.entity.statements );
				expect( state.entity.id ).toBe( response.entity.id );
				expect( state.entity.baseRevision ).toBe( response.revisionId );
				expect( state.applicationStatus ).toBe( ApplicationStatus.READY );
			} );
		} );
	} );

	describe( 'composed actions', () => {
		describe( 'setStringDataValue', () => {
			it( 'rejects on unknown Entity', async () => {
				expect.assertions( 1 );
				await expect( store.dispatch(
					action( NS_STATEMENTS, SNAK_SET_STRING_DATA_VALUE ),
					{
						path: new MainSnakPath(
							'Q3333333',
							'P23',
							0,
						),
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
					action( NS_STATEMENTS, SNAK_SET_STRING_DATA_VALUE ),
					{
						path: new MainSnakPath(
							'Q42',
							'P99999',
							0,
						),
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
					action( NS_STATEMENTS, SNAK_SET_STRING_DATA_VALUE ),
					{
						path: new MainSnakPath(
							'Q42',
							'P31',
							42,
						),
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
					action( NS_STATEMENTS, SNAK_SET_STRING_DATA_VALUE ),
					{
						path: new MainSnakPath(
							'Q42',
							'P42',
							0,
						),
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
					action( NS_STATEMENTS, SNAK_SET_STRING_DATA_VALUE ),
					{
						path: new MainSnakPath(
							'Q42',
							'P42',
							0,
						),
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
						action( NS_STATEMENTS, SNAK_SET_STRING_DATA_VALUE ),
						{
							path: new MainSnakPath(
								'Q42',
								'P31',
								0,
							),
							value,
						},
					).then( () => {
						const state = store.state as InitializedApplicationState;
						expect( state.statements.Q42.P31[ 0 ].mainsnak.datavalue ).toBe( value );
					} );
				} );

				it( 'sets the snak type to value', () => {
					const value = {
						type: 'string',
						value: 'passed teststring',
					};

					return store.dispatch(
						action( NS_STATEMENTS, SNAK_SET_STRING_DATA_VALUE ),
						{
							path: new MainSnakPath(
								'Q42',
								'P60',
								0,
							),
							value,
						},
					).then( () => {
						const state = store.state as InitializedApplicationState;
						expect( state.statements.Q42.P60[ 0 ].mainsnak.snaktype ).toBe( 'value' );
					} );
				} );
			} );
		} );
	} );
} );
