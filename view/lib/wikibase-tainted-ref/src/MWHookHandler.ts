import { HookHandler } from '@/HookHandler';
import { Store } from 'vuex';
import Application from '@/store/Application';
import {
	START_EDIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
	STOP_EDIT,
} from '@/store/actionTypes';
import { HookRegistry } from '@/@types/mediawiki/MwWindow';
import TaintedChecker from '@/TaintedChecker';
import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';
import StatementTracker from '@/StatementTracker';
export default class MWHookHandler implements HookHandler {
	private mwHooks: HookRegistry;
	private taintedChecker: TaintedChecker;
	private statementTracker: StatementTracker;

	public constructor( mWHooks: HookRegistry, taintedChecker: TaintedChecker, statementTracker: StatementTracker ) {
		this.mwHooks = mWHooks;
		this.taintedChecker = taintedChecker;
		this.statementTracker = statementTracker;
	}

	public addStore( store: Store<Application> ): void {
		this.addStartEditingHook( store );
		this.addSaveHook( store );
		this.addStopEditingHook( store );
	}

	private addStartEditingHook( store: Store<Application> ): void {
		this.mwHooks( 'wikibase.statement.startEditing' ).add( ( guid: string ) => {
			store.dispatch( START_EDIT, guid );
		} );
	}

	private addSaveHook( store: Store<Application> ): void {
		this.mwHooks( 'wikibase.statement.saved' ).add(
			( _entityId: string, statementId: string, oldStatement: Statement|null, newStatement: Statement ) => {
				if ( store.state.statementsTaintedState[ statementId ] ) {
					store.dispatch( STATEMENT_TAINTED_STATE_UNTAINT, statementId );
				} else if ( this.taintedChecker.check( oldStatement, newStatement ) ) {
					store.dispatch( STATEMENT_TAINTED_STATE_TAINT, statementId );
				}
			},
		);
		this.mwHooks( 'wikibase.statement.saved' ).add(
			( _entityId: string, _statementId: string, oldStatement: Statement|null, newStatement: Statement ) => {
				this.statementTracker.trackChanges( oldStatement, newStatement );
			},
		);
	}

	private addStopEditingHook( store: Store<Application> ): void {
		this.mwHooks( 'wikibase.statement.stopEditing' ).add( ( guid: string ) => {
			store.dispatch( STOP_EDIT, guid );
		} );
	}
}
