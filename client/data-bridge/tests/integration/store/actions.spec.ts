import { Store } from 'vuex';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import Application, { InitializedApplicationState } from '@/store/Application';
import EditFlow from '@/definitions/EditFlow';
import EntityRevision from '@/datamodel/EntityRevision';
import AppInformation from '@/definitions/AppInformation';
import ServiceContainer from '@/services/ServiceContainer';
import { createStore } from '@/store';
import { NS_ENTITY } from '@/store/namespaces';
import { action } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import Term from '@/datamodel/Term';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import { PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import DataValue from '@/datamodel/DataValue';
import clone from '@/store/clone';
import EditDecision from '@/definitions/EditDecision';
import newMockTracker from '../../util/newMockTracker';
import { getMockBridgeRepoConfig } from '../../util/mocks';

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
				return getMockBridgeRepoConfig();
			},
		} );

		services.set( 'propertyDatatypeRepository', {
			getDataType: jest.fn().mockResolvedValue( 'string' ),
		} );

		services.set( 'tracker', newMockTracker() );

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
		await store.dispatch( 'initBridge', info );
	} );

	function getStatementModuleDataValue(
		state: InitializedApplicationState,
		entityId = info.entityId,
		propertyId = info.propertyId,
	): DataValue|undefined {
		return state
			.statements[ entityId ][ propertyId ][ 0 ]
			.mainsnak.datavalue;
	}

	describe( 'application initiation', () => {
		it( 'successfully initiate on valid input data', () => {
			const successStore = createStore( services );
			return successStore.dispatch( 'initBridge', info ).then( () => {
				expect( successStore.state.applicationStatus ).toBe( ApplicationStatus.READY );
				expect( successStore.state.targetLabel ).toBe( labelTerm );
				expect( successStore.state.targetValue ).not.toBe(
					testSet.entity.statements[ info.propertyId ][ 0 ].mainsnak.datavalue,
				);
				expect( successStore.state.targetValue ).toStrictEqual(
					testSet.entity.statements[ info.propertyId ][ 0 ].mainsnak.datavalue!,
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

				return errorStore.dispatch( 'initBridge', misleadingInfo ).then( () => {
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
				return errorStore.dispatch( 'initBridge', misleadingInfo ).then( () => {
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
				return errorStore.dispatch( 'initBridge', misleadingInfo ).then( () => {
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
				return errorStore.dispatch( 'initBridge', misleadingInfo ).then( () => {
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
				return permissionProblemStore.dispatch( 'initBridge', misleadingInfo ).then( () => {
					expect( permissionProblemStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
				} );
			} );
		} );
	} );

	describe( 'entitySave', () => {
		it( 'rejects if the request fails', async () => {
			const rejectError = new Error( 'no' );
			const saveEntity = jest.fn().mockRejectedValue( rejectError );

			services.set( 'writingEntityRepository', {
				saveEntity,
			} );

			store = createStore( services );
			await store.dispatch( 'initBridge', info );
			await expect(
				store.dispatch(
					action( NS_ENTITY, 'entitySave' ),
					testSet.entity.statements,
				),
			).rejects.toBe( rejectError );

			expect( saveEntity ).toHaveBeenCalledWith( testSet );
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

			const saveEntity = jest.fn().mockResolvedValue( response );

			services.set( 'writingEntityRepository', {
				saveEntity,
			} );

			store = createStore( services );
			await store.dispatch( 'initBridge', info );

			await store.dispatch( action( NS_ENTITY, 'entitySave' ), testSet.entity.statements );

			expect( saveEntity ).toHaveBeenCalledWith( testSet );

			const state = store.state as InitializedApplicationState;
			expect( state.statements ).toEqual( { Q42: response.entity.statements } );
			expect( state.entity.id ).toBe( response.entity.id );
			expect( state.entity.baseRevision ).toBe( response.revisionId );
		} );
	} );

	describe( 'alias actions', () => {
		describe( 'setTargetValue', () => {
			it( 'rejects if the store is not ready and switches to error state', async () => {
				const notReadyStore = createStore( services );
				await expect( notReadyStore.dispatch(
					'setTargetValue',
					{ type: 'string', dataValue: 'passing string' },
				) ).rejects.toBeDefined();
				expect( notReadyStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
			} );

			it( 'sets the new data value', () => {
				const dataValue = { type: 'string', value: 'Töften as passing string' };
				return store.dispatch(
					'setTargetValue',
					dataValue,
				).then( () => {
					const state = ( store.state as InitializedApplicationState );
					expect( state.targetValue ).toStrictEqual( dataValue );
				} );
			} );
		} );

		describe( 'saveBridge', () => {
			it( 'rejects if the store is not ready and switches to error state', async () => {
				const notReadyStore = createStore( services );
				await expect( notReadyStore.dispatch(
					'saveBridge',
					{ type: 'string', dataValue: 'passing string' },
				) ).rejects.toBeDefined();
				expect( notReadyStore.state.applicationErrors.length ).toBeGreaterThan( 0 );
			} );

			it( 'rejects, switches to error, keeps statements unchanged if the request fails', async () => {
				const rejectError = new Error( 'no' );
				const saveEntity = jest.fn().mockRejectedValue( rejectError );

				services.set( 'writingEntityRepository', {
					saveEntity,
				} );

				store = createStore( services );
				await store.dispatch( 'initBridge', info );
				await store.dispatch( 'setEditDecision', EditDecision.REPLACE );

				const state = store.state as InitializedApplicationState;
				const statementAfterInit = clone( getStatementModuleDataValue( state )! );

				await expect(
					store.dispatch( 'saveBridge' ),
				).rejects.toBe( rejectError );

				expect( saveEntity ).toHaveBeenCalledWith( testSet );
				expect( store.state.applicationErrors.length ).toBeGreaterThan( 0 );
				expect( getStatementModuleDataValue( state )! ).toStrictEqual( statementAfterInit );
			} );

			it( 'stores the responded entity & purges the page, if the request succeeded', async () => {
				const newStringValue = 'new value';
				const saveResponse = {
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
										value: newStringValue,
									},
								},
							} ],
						},
					},
				};

				const saveEntity = jest.fn().mockResolvedValue( saveResponse );

				services.set( 'writingEntityRepository', {
					saveEntity,
				} );

				const purge = jest.fn().mockReturnValue( Promise.resolve() );
				services.set( 'purgeTitles', {
					purge,
				} );

				store = createStore( services );

				await store.dispatch( 'initBridge', info );

				await store.dispatch( 'setTargetValue', {
					type: 'string',
					value: newStringValue,
				} );
				await store.dispatch( 'setEditDecision', EditDecision.REPLACE );

				await store.dispatch( 'saveBridge' );

				const testSetChangedByUserInteraction = clone( testSet );
				testSetChangedByUserInteraction
					.entity
					.statements.P31[ 0 ]
					.mainsnak.datavalue!.value = newStringValue;

				expect( saveEntity ).toHaveBeenCalledWith( testSetChangedByUserInteraction );

				const state = ( store.state as InitializedApplicationState );
				expect( state.statements.Q42 ).toEqual( saveResponse.entity.statements );
				expect( state.entity.id ).toBe( saveResponse.entity.id );
				expect( state.entity.baseRevision ).toBe( saveResponse.revisionId );

				expect( purge ).toHaveBeenCalledWith( [ info.pageTitle ] );

				expect( state.applicationStatus ).toBe( ApplicationStatus.READY );
			} );
		} );
	} );
} );
