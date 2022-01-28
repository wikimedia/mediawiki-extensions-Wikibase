import {
	START_EDIT,
	STOP_EDIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
} from '@/store/actionTypes';
import MWHookHandler from '@/MWHookHandler';
import Vuex, { Store } from 'vuex';
import { Hook, HookRegistry } from '@/@types/mediawiki/MwWindow';
import getMockStatement from './getMockStatement';
import StatementTracker from '@/StatementTracker';
import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';
import { HookHandler } from '@/HookHandler';

const fakeGuid = 'cat-Guid';

function getMockStatementTracker(): StatementTracker {
	return { trackChanges: jest.fn() } as any;

}
function getDummyEditHook( guid: string ): Hook {
	const dummyEditHook = ( randomFunction: Function ): Hook => {
		randomFunction( guid );
		return { add: jest.fn() };
	};
	return dummyEditHook as any as Hook;
}

function getDummySaveHook( entityId: string, fakeGuid: string, s1?: Statement|null, s2?: Statement ): Hook {
	const dummySaveHook = ( randomFunction: Function ): Hook => {
		randomFunction( entityId, fakeGuid, s1, s2 );
		return { add: jest.fn() };
	};
	return dummySaveHook as any as Hook;
}

function getMockHookRegistry( mwHookName: string, mockHook: Hook ): HookRegistry {
	const mwHookRegistry = jest.fn( ( hookName: string ) => {
		if ( hookName === mwHookName ) {
			return { add: mockHook };
		} else {
			return { add: jest.fn() };
		}
	} );
	return mwHookRegistry as any as HookRegistry;
}

function getEmptyInitialisedStore(): any {
	return new Vuex.Store( { state: { statementsTaintedState: {}, statementsPopperIsOpen: {} } as any } );
}

describe( 'MWHookHandler', () => {
	describe( 'on start edit hook firing', () => {
		it( `should dispatch ${START_EDIT} with statement guid`, () => {
			const dummyEditHook = getDummyEditHook( fakeGuid );
			const startEditingHookName = 'wikibase.statement.startEditing';
			const mwHookRegistry = getMockHookRegistry( startEditingHookName, dummyEditHook );
			const mockTaintedChecker = { check: () => true };
			const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
			const store = getEmptyInitialisedStore();
			store.dispatch = jest.fn();

			hookHandler.addStore( store );

			expect( mwHookRegistry ).toHaveBeenCalledWith( startEditingHookName );
			expect( store.dispatch ).toHaveBeenCalledWith( START_EDIT, fakeGuid );
		} );
	} );

	describe( 'on stop edit hook firing', () => {
		it( `should dispatch ${STOP_EDIT} with statement guid`, () => {
			const dummyEditHook = getDummyEditHook( fakeGuid );
			const stopEditingHookName = 'wikibase.statement.stopEditing';
			const mwHookRegistry = getMockHookRegistry( stopEditingHookName, dummyEditHook );
			const mockTaintedChecker = { check: () => true };
			const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
			const store = getEmptyInitialisedStore();
			store.dispatch = jest.fn();

			hookHandler.addStore( store );

			expect( mwHookRegistry ).toHaveBeenCalledWith( stopEditingHookName );
			expect( store.dispatch ).toHaveBeenCalledWith( STOP_EDIT, fakeGuid );
		} );
	} );

	describe( 'on save hook firing', () => {
		function getStoreWithPreTaintedStatement(): Store<any> {
			const store = new Vuex.Store( {
				state: {
					statementsTaintedState: { [ fakeGuid ]: true },
					statementsPopperIsOpen: {},
				} as any,
			} );
			return store;
		}

		function getSavingHookHandler( taintedCheck: boolean ): HookHandler {
			const dummySaveHook = getDummySaveHook( 'Q1', fakeGuid );
			const saveHookName = 'wikibase.statement.saved';
			const mwHookRegistry = getMockHookRegistry( saveHookName, dummySaveHook );
			const mockTaintedChecker = { check: () => taintedCheck };
			const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
			return hookHandler;
		}

		it( `should dispatch ${STATEMENT_TAINTED_STATE_TAINT} with statement guid ` +
			'when taintedChecker is true and statement is untainted', () => {
			const hookHandler = getSavingHookHandler( true );
			const store = getEmptyInitialisedStore();
			store.dispatch = jest.fn();

			hookHandler.addStore( store );

			expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_TAINT, fakeGuid );
		} );

		it( `should not dispatch ${STATEMENT_TAINTED_STATE_TAINT} ` +
			'when taintedChecker is false', () => {
			const s1 = getMockStatement( false );
			const s2 = getMockStatement( false );
			const entityId = 'Q1';
			const saveHookName = 'wikibase.statement.saved';
			const dummySaveHook = getDummySaveHook( entityId, fakeGuid, s1, s2 );
			const mwHookRegistry = getMockHookRegistry( saveHookName, dummySaveHook );
			const mockTaintedChecker = { check: jest.fn() };
			mockTaintedChecker.check.mockReturnValue( false );
			const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
			const store = getEmptyInitialisedStore();
			store.dispatch = jest.fn();

			hookHandler.addStore( store );

			expect( mockTaintedChecker.check ).toHaveBeenCalledWith( s1, s2 );
			expect( mwHookRegistry ).toHaveBeenCalledWith( saveHookName );
			expect( store.dispatch ).not.toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_TAINT, fakeGuid );
		} );

		it( `should dispatch ${STATEMENT_TAINTED_STATE_UNTAINT} with statement guid ` +
			'if statement was already tainted and taintedChecker is true', () => {
			const hookHandler = getSavingHookHandler( true );
			const store = getStoreWithPreTaintedStatement();
			store.dispatch = jest.fn();

			hookHandler.addStore( store );

			expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, fakeGuid );
		} );

		it( `should dispatch ${STATEMENT_TAINTED_STATE_UNTAINT} with statement guid ` +
			'if statement was already tainted and taintedChecker is false', () => {
			const hookHandler = getSavingHookHandler( false );
			const store = getStoreWithPreTaintedStatement();
			store.dispatch = jest.fn();

			hookHandler.addStore( store );

			expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, fakeGuid );
		} );

		it( 'should call the statementTracker', () => {
			const s1 = getMockStatement( false );
			const s2 = getMockStatement( false );
			const entityId = 'Q1';
			const dummySaveHook = getDummySaveHook( entityId, fakeGuid, s1, s2 );
			const mwHookRegistry = getMockHookRegistry( 'wikibase.statement.saved', dummySaveHook );
			const mockTaintedChecker = { check: () => true };
			const trackChanges = jest.fn();
			const store = getEmptyInitialisedStore();
			store.dispatch = jest.fn();
			const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, { trackChanges } as any );

			hookHandler.addStore( store );

			expect( trackChanges ).toHaveBeenCalledWith( s1, s2 );
		} );

		describe( 'when there is no old statement', () => {
			it( 'should not dispatch anything', () => {
				const s = getMockStatement( false );
				const entityId = 'Q1';
				const dummySaveHook = getDummySaveHook( entityId, fakeGuid, null, s );
				const mwHookRegistry = getMockHookRegistry( 'wikibase.statement.saved', dummySaveHook );
				const mockTaintedChecker = { check: () => false };
				const store = getEmptyInitialisedStore();
				store.dispatch = jest.fn();
				const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );

				hookHandler.addStore( store );

				expect( store.dispatch ).not.toHaveBeenCalled();
			} );

			it( 'should call the statementTracker', () => {
				const s = getMockStatement( false );
				const entityId = 'Q1';
				const dummySaveHook = getDummySaveHook( entityId, fakeGuid, null, s );
				const mwHookRegistry = getMockHookRegistry( 'wikibase.statement.saved', dummySaveHook );
				const mockTaintedChecker = { check: () => false };
				const trackChanges = jest.fn();
				const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, { trackChanges } as any );

				hookHandler.addStore( getEmptyInitialisedStore() );

				expect( trackChanges ).toHaveBeenCalledWith( null, s );
			} );
		} );
	} );

} );
