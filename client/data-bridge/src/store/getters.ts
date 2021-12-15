import { Reference } from '@wmde/wikibase-datamodel-types';
import { Store } from 'vuex';
import Status from '@/definitions/ApplicationStatus';
import Application, { BridgeConfig, InitializedApplicationState } from '@/store/Application';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import Term from '@/datamodel/Term';
import deepEqual from 'deep-equal';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { Context, Getters } from 'vuex-smart-module';
import { statementModule } from '@/store/statements';
import { ErrorTypes, SavingFailedError } from '@/definitions/ApplicationError';
import { serializeError } from 'serialize-error';

export class RootGetters extends Getters<Application> {

	private statementModule!: Context<typeof statementModule>;

	public $init( store: Store<Application> ): void {
		this.statementModule = statementModule.context( store );
	}

	public get targetLabel(): Term {
		return this.state.targetLabel ?? {
			language: 'zxx',
			value: this.state.targetProperty,
		};
	}

	public get targetReferences(): Reference[] {
		try {
			const activeState = this.state as InitializedApplicationState;
			const entityId = activeState[ NS_ENTITY ].id;
			const statement = activeState[ NS_STATEMENTS ][ entityId ][ this.state.targetProperty ][ 0 ];

			return statement.references ?? [];
		} catch ( _ignored ) {
			return [];
		}
	}

	public get isTargetValueModified(): boolean {
		if ( this.state.applicationStatus === Status.INITIALIZING ) {
			return false;
		}

		const initState = this.state as InitializedApplicationState;
		const entityId = initState[ NS_ENTITY ].id;
		return !deepEqual(
			this.state.targetValue,
			initState[ NS_STATEMENTS ][ entityId ][ this.state.targetProperty ][ 0 ]
				.mainsnak
				.datavalue,
			{ strict: true },
		);
	}

	public get canStartSaving(): boolean {
		return this.state.editDecision !== null &&
			this.getters.isTargetValueModified &&
			this.getters.applicationStatus === ApplicationStatus.READY;
	}

	public get isGenericSavingError(): boolean {
		return this.state.applicationErrors.length > 0 && this.state.applicationErrors.every(
			( error ) => {
				return [
					ErrorTypes.SAVING_FAILED,
					ErrorTypes.BAD_TAGS,
					ErrorTypes.NO_SUCH_REVID,
					ErrorTypes.ASSERT_NAMED_USER_FAILED,
				].includes( ( error as SavingFailedError ).type );
			},
		);
	}

	public get isAssertUserFailedError(): boolean {
		return this.state.applicationErrors.length === 1
			&& this.state.applicationErrors[ 0 ].type === ErrorTypes.ASSERT_USER_FAILED;
	}

	public get isEditConflictError(): boolean {
		return this.state.applicationErrors.length === 1
			&& this.state.applicationErrors[ 0 ].type === ErrorTypes.EDIT_CONFLICT;
	}

	public get canGoToPreviousState(): boolean {
		return this.getters.isGenericSavingError || this.getters.isAssertUserFailedError;
	}

	public get applicationStatus(): ApplicationStatus {
		if ( this.state.applicationErrors.length > 0 ) {
			return ApplicationStatus.ERROR;
		}

		return this.state.applicationStatus;
	}

	public get reportIssueTemplateBody(): string {
		const pageUrl = this.state.pageUrl;
		const serializedApplicationErrors = this.state.applicationErrors.map( ( error ) => serializeError( error ) );
		const stackTrace = JSON.stringify( serializedApplicationErrors, null, 4 );

		return [ `The error happened on: ${pageUrl}`,
			`Item title: ${this.state.entityTitle}`,
			`Property: ${this.state.targetProperty}`,
			`Error message: ${this.state.applicationErrors[ 0 ].type}`,
			`Approximate time of request: ${( new Date() ).toISOString()}`,
			'Debug information:',
			'```',
			stackTrace,
			'```',
		].join( '\n' );
	}

	public get issueReportingLinkConfig(): string {
		const bridgeConfig = this.state.config;
		if ( bridgeConfig.issueReportingLink === null ) {
			throw new Error( 'not correctly initialized' );
		}
		return bridgeConfig.issueReportingLink;
	}

	public get config(): BridgeConfig {
		return this.state.config;
	}

}
