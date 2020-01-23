import {
	START_EDIT,
	STOP_EDIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
} from '@/store/actionTypes';
import MWHookHandler from '@/MWHookHandler';
import Vuex from 'vuex';
import Vue from 'vue';
import { Hook, HookRegistry } from '@/@types/mediawiki/MwWindow';
import getMockStatement from './getMockStatement';
import StatementTracker from '@/StatementTracker';
import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';

Vue.use( Vuex );

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

function getDummySaveHook( entityId: string, fakeGuid: string, s1?: Statement, s2?: Statement ): Hook {
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
	it( `should dispatch ${START_EDIT} with statement guid on edit hook firing`, () => {
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

	it( `should dispatch ${STOP_EDIT} with statement guid on edit stop hook firing`, () => {
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

	it( `should dispatch ${STATEMENT_TAINTED_STATE_TAINT} with statement guid` +
		'on save hook firing and checker is true', () => {
		const dummySaveHook = getDummySaveHook( 'Q1', fakeGuid );
		const saveHookName = 'wikibase.statement.saved';

		const mwHookRegistry = getMockHookRegistry( saveHookName, dummySaveHook );

		const mockTaintedChecker = { check: () => true };
		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
		const store = getEmptyInitialisedStore();
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( saveHookName );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_TAINT, fakeGuid );
	} );

	it( 'should not dispatch ${STATEMENT_TAINTED_STATE_TAINT} ' +
		'on save hook firing when checker is false', () => {
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
		'on save hook firing, if statement was tainted', () => {
		const dummySaveHook = getDummySaveHook( 'Q1', fakeGuid );
		const saveHookName = 'wikibase.statement.saved';

		const mwHookRegistry = getMockHookRegistry( saveHookName, dummySaveHook );

		const mockTaintedChecker = { check: () => true };
		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
		const store = new Vuex.Store( {
			state: {
				statementsTaintedState: { [ fakeGuid ]: true },
				statementsPopperIsOpen: {},
			} as any,
		} );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( saveHookName );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, fakeGuid );
	} );

	it( 'should call the statementTracker on save hook firing', () => {
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

	it( `should dispatch ${START_EDIT} with statement guid on start edit hook firing`, () => {
		const dummyStartEditHook = getDummyEditHook( fakeGuid );
		const startEditingHookName = 'wikibase.statement.startEditing';

		const mwHookRegistry = getMockHookRegistry( startEditingHookName, dummyStartEditHook );

		const mockTaintedChecker = { check: () => true };

		const store = getEmptyInitialisedStore();
		store.dispatch = jest.fn();
		const trackChanges = jest.fn();
		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, { trackChanges } as any );
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( startEditingHookName );
		expect( store.dispatch ).toHaveBeenCalledWith( START_EDIT, fakeGuid );
	} );
} );
