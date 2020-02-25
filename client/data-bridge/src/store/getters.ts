import { Store } from 'vuex';
import Status from '@/definitions/ApplicationStatus';
import Application, { InitializedApplicationState } from '@/store/Application';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
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
		if ( this.state.applicationStatus === Status.INITIALIZING ) {
			return [];
		}

		const activeState = this.state as InitializedApplicationState;
		const entityId = activeState[ NS_ENTITY ].id;
		const statements = activeState[ NS_STATEMENTS ][ entityId ][ this.state.targetProperty ][ 0 ];

		return statements.references ? statements.references : [];
	}

	public get isTargetValueModified(): boolean {
		if ( this.state.applicationStatus === Status.INITIALIZING ) {
			return false;
		}

		const initState = this.state as InitializedApplicationState;
		const entityId = initState[ NS_ENTITY ].id;
		return !deepEqual(
			this.state.targetValue,
			( initState[ NS_STATEMENTS ][ entityId ][ this.state.targetProperty ][ 0 ] as Statement )
				.mainsnak
				.datavalue,
			{ strict: true },
		);
	}

	public get canSave(): boolean {
		return this.state.editDecision !== null &&
			this.getters.isTargetValueModified;
	}

	public get applicationStatus(): ApplicationStatus {
		if ( this.state.applicationErrors.length > 0 ) {
			return ApplicationStatus.ERROR;
		}

		return this.state.applicationStatus;
	}

}
