import Vue from 'vue';
import {
	Store,
} from 'vuex';
import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin';
import Application, {
	InitializedApplicationState,
} from '@/store/Application';
import {
	BRIDGE_ERROR_ADD,
	BRIDGE_INIT,
	BRIDGE_INIT_WITH_REMOTE_DATA,
	BRIDGE_REQUEST_TARGET_LABEL,
	BRIDGE_SAVE,
	BRIDGE_SET_EDIT_DECISION,
	BRIDGE_SET_TARGET_VALUE,
	BRIDGE_VALIDATE_APPLICABILITY,
	BRIDGE_VALIDATE_ENTITY_STATE,
} from '@/store/actionTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import AppInformation from '@/definitions/AppInformation';
import EditDecision from '@/definitions/EditDecision';
import {
	APPLICATION_ERRORS_ADD,
	APPLICATION_STATUS_SET,
	EDITFLOW_SET,
	PROPERTY_TARGET_SET,
	TARGET_LABEL_SET,
	ORIGINAL_STATEMENT_SET,
	EDITDECISION_SET,
	ENTITY_TITLE_SET,
	ORIGINAL_HREF_SET,
	PAGE_TITLE_SET,
} from '@/store/mutationTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/statements/getterTypes';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import DataValue from '@/datamodel/DataValue';
import { MainSnakPath } from '@/store/statements/MainSnakPath';
import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import { Actions, Context } from 'vuex-smart-module';
import { RootGetters } from '@/store/getters';
import { RootMutations } from '@/store/mutations';
import Term from '@/datamodel/Term';
import { entityModule } from './entity';
import { statementModule } from '@/store/statements';
import {
	SNAK_DATATYPE,
	SNAK_DATAVALUETYPE,
	SNAK_SNAKTYPE,
} from '@/store/statements/snaks/getterTypes';
import { SNAK_SET_STRING_DATA_VALUE } from '@/store/statements/snaks/actionTypes';
import { MissingPermissionsError } from '@/definitions/data-access/BridgePermissionsRepository';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import ServiceContainer from '@/services/ServiceContainer';

export class RootActions extends Actions<
Application,
RootGetters,
RootMutations,
RootActions
> {
	private store!: { $services: ServiceContainer };
	private entityModule!: Context<typeof entityModule>;
	private statementModule!: Context<typeof statementModule>;
	public $init( store: Store<Application> ): void {
		this.store = store;
		this.entityModule = entityModule.context( store );
		this.statementModule = statementModule.context( store );
	}

	public [ BRIDGE_INIT ](
		information: AppInformation,
	): Promise<void> {
		this.commit( EDITFLOW_SET, information.editFlow );
		this.commit( PROPERTY_TARGET_SET, information.propertyId );
		this.commit( ENTITY_TITLE_SET, information.entityTitle );
		this.commit( ORIGINAL_HREF_SET, information.originalHref );
		this.commit( PAGE_TITLE_SET, information.pageTitle );

		this.dispatch( BRIDGE_REQUEST_TARGET_LABEL, information.propertyId );

		return Promise.all( [
			this.store.$services.get( 'wikibaseRepoConfigRepository' ).getRepoConfiguration(),
			this.store.$services.get( 'editAuthorizationChecker' ).canUseBridgeForItemAndPage(
				information.entityTitle,
				information.pageTitle,
			),
			this.store.$services.get( 'propertyDatatypeRepository' ).getDataType( information.propertyId ),
			this.entityModule.dispatch(
				ENTITY_INIT,
				{ entity: information.entityId },
			),
		] ).then(
			( results ) => this.dispatch( BRIDGE_INIT_WITH_REMOTE_DATA, { information, results } ),
			( error ) => {
				this.commit( APPLICATION_ERRORS_ADD, [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: error } ] );
				throw error;
			},
		);
	}

	public async [ BRIDGE_INIT_WITH_REMOTE_DATA ]( {
		information,
		results: [
			wikibaseRepoConfiguration,
			permissionErrors,
			dataType,
			_entityInit,
		],
	}: {
		information: AppInformation;
		results: [ WikibaseRepoConfiguration, MissingPermissionsError[], string, unknown ];
	} ): Promise<void> {
		if ( permissionErrors.length ) {
			this.commit( APPLICATION_ERRORS_ADD, permissionErrors );
			return;
		}

		this.store.$services.get( 'tracker' ).trackPropertyDatatype( dataType );

		BridgeConfig( Vue, { ...wikibaseRepoConfiguration, ...information.client } );
		const state = this.state as InitializedApplicationState;

		const path = new MainSnakPath(
			state[ NS_ENTITY ].id,
			state.targetProperty,
			0,
		);

		await this.dispatch( BRIDGE_VALIDATE_ENTITY_STATE, path );
		if ( this.getters.applicationStatus !== ApplicationStatus.ERROR ) {
			this.commit(
				ORIGINAL_STATEMENT_SET,
				state[ NS_STATEMENTS ][ path.entityId ][ path.propertyId ][ path.index ],
			);

			this.commit(
				APPLICATION_STATUS_SET,
				ApplicationStatus.READY,
			);
		}
	}

	public [ BRIDGE_REQUEST_TARGET_LABEL ]( propertyId: string ): Promise<void> {
		return this.store.$services.get( 'entityLabelRepository' ).getLabel( propertyId )
			.then( ( label: Term ) => {
				this.commit( TARGET_LABEL_SET, label );
			}, ( _error: Error ) => {
				// TODO: handling on failed label loading, which is not a bocking error for now
			} );
	}

	public [ BRIDGE_VALIDATE_ENTITY_STATE ](
		path: MainSnakPath,
	): Promise<void> {
		if (
			this.statementModule.getters[ STATEMENTS_PROPERTY_EXISTS ]( path.entityId, path.propertyId ) === false
		) {
			this.commit( APPLICATION_ERRORS_ADD, [ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ] );
			return Promise.resolve();
		}

		return this.dispatch( BRIDGE_VALIDATE_APPLICABILITY, path );
	}

	public [ BRIDGE_VALIDATE_APPLICABILITY ](
		path: MainSnakPath,
	): Promise<void> {
		if (
			this.statementModule.getters[ STATEMENTS_IS_AMBIGUOUS ]( path.entityId, path.propertyId ) === true
		) {
			return this.dispatch( BRIDGE_ERROR_ADD, [ { type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT } ] );
		}

		if (
			this.statementModule.getters[ SNAK_SNAKTYPE ]( path ) !== 'value'
		) {
			return this.dispatch( BRIDGE_ERROR_ADD, [ { type: ErrorTypes.UNSUPPORTED_SNAK_TYPE } ] );
		}

		const datatype = this.statementModule.getters[ SNAK_DATATYPE ]( path );
		if ( datatype === null ) {
			throw new Error( 'If snak is missing, there should have been an error earlier' );
		}
		if ( datatype !== 'string' ) {
			const error: ApplicationError = {
				type: ErrorTypes.UNSUPPORTED_DATATYPE,
				info: {
					unsupportedDatatype: datatype,
					supportedDatatypes: [ 'string' ],
				},
			};
			return this.dispatch( BRIDGE_ERROR_ADD, [ error ] );
		}

		if (
			this.statementModule.getters[ SNAK_DATAVALUETYPE ]( path ) !== 'string'
		) {
			return this.dispatch( BRIDGE_ERROR_ADD, [ { type: ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE } ] );
		}

		return Promise.resolve();
	}

	public [ BRIDGE_SET_TARGET_VALUE ](
		dataValue: DataValue,
	): Promise<void> {
		if ( this.state.applicationStatus !== ApplicationStatus.READY ) {
			this.commit( APPLICATION_ERRORS_ADD, [ {
				type: ErrorTypes.APPLICATION_LOGIC_ERROR,
				info: { stack: ( new Error() ).stack },
			} ] );
			return Promise.reject( null );
		}

		const state = this.state as InitializedApplicationState;
		const path = new MainSnakPath(
			state[ NS_ENTITY ].id,
			state.targetProperty,
			0,
		);

		return this.statementModule.dispatch( SNAK_SET_STRING_DATA_VALUE,
			{
				path,
				value: dataValue,
			} ).catch( ( error: Error ) => {
			this.commit( APPLICATION_ERRORS_ADD, [ {
				type: ErrorTypes.APPLICATION_LOGIC_ERROR,
				info: error,
			} ] );
			throw error;
		} );
	}

	public [ BRIDGE_SAVE ](): Promise<void> {
		if ( this.state.applicationStatus !== ApplicationStatus.READY ) {
			this.commit( APPLICATION_ERRORS_ADD, [ {
				type: ErrorTypes.APPLICATION_LOGIC_ERROR,
				info: { stack: ( new Error() ).stack },
			} ] );
			return Promise.reject( null );
		}

		return this.entityModule.dispatch( ENTITY_SAVE )
			.catch( ( error: Error ) => {
				this.commit( APPLICATION_ERRORS_ADD, [ { type: ErrorTypes.SAVING_FAILED, info: error } ] );
				throw error;
			} );
	}

	public [ BRIDGE_ERROR_ADD ](
		errors: ApplicationError[],
	): Promise<void> {
		this.commit( APPLICATION_ERRORS_ADD, errors );
		return Promise.resolve();
	}

	public [ BRIDGE_SET_EDIT_DECISION ](
		editDecision: EditDecision,
	): Promise<void> {
		this.commit( EDITDECISION_SET, editDecision );
		return Promise.resolve();
	}

}
