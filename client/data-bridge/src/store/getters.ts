import { Store } from 'vuex';
import Status from '@/definitions/ApplicationStatus';
import DataValue from '@/datamodel/DataValue';
import Application, { InitializedApplicationState } from '@/store/Application';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import { MainSnakPath } from '@/store/statements/MainSnakPath';
import Term from '@/datamodel/Term';
import Statement from '@/datamodel/Statement';
import Reference from '@/datamodel/Reference';
import deepEqual from 'deep-equal';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { Context, Getters } from 'vuex-smart-module';
import { statementModule } from '@/store/statements';

export class RootGetters extends Getters<Application> {

	private statementModule!: Context<typeof statementModule>;
	public $init( store: Store<Application> ): void {
		this.statementModule = statementModule.context( store );
	}

	public get targetValue(): DataValue|null {
		if ( this.state.applicationStatus !== Status.READY ) {
			return null;
		}

		const entityId = ( this.state as InitializedApplicationState )[ NS_ENTITY ].id;
		const pathToMainSnak = new MainSnakPath( entityId, this.state.targetProperty, 0 );

		return this.statementModule.getters.dataValue( pathToMainSnak );
	}

	public get targetLabel(): Term {
		if ( this.state.targetLabel === null ) {
			return {
				language: 'zxx',
				value: this.state.targetProperty,
			};
		}

		return this.state.targetLabel;
	}

	public get targetReferences(): Reference[] {
		if ( this.state.applicationStatus !== Status.READY ) {
			return [];
		}

		const activeState = this.state as InitializedApplicationState;
		const entityId = activeState[ NS_ENTITY ].id;
		const statements = activeState[ NS_STATEMENTS ][ entityId ][ this.state.targetProperty ][ 0 ];

		return statements.references ? statements.references : [];
	}

	public get isTargetStatementModified(): boolean {
		if ( this.state.applicationStatus !== Status.READY ) {
			return false;
		}

		const initState = this.state as InitializedApplicationState;
		const entityId = initState[ NS_ENTITY ].id;
		return !deepEqual(
			this.state.originalStatement as Statement,
			initState[ NS_STATEMENTS ][ entityId ][ this.state.targetProperty ][ 0 ],
			{ strict: true },
		);
	}

	public get canSave(): boolean {
		return this.state.editDecision !== null &&
			this.getters.isTargetStatementModified;
	}

	public get applicationStatus(): ApplicationStatus {
		if ( this.state.applicationErrors.length > 0 ) {
			return ApplicationStatus.ERROR;
		}

		return this.state.applicationStatus;
	}

}
