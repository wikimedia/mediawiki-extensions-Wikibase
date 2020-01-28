import Vue from 'vue';
import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin';
import {
	ActionContext,
	ActionTree,
} from 'vuex';
import Application, {
	InitializedApplicationState,
} from '@/store/Application';
import {
	BRIDGE_ERROR_ADD,
	BRIDGE_INIT,
	BRIDGE_SAVE,
	BRIDGE_SET_EDIT_DECISION,
	BRIDGE_SET_TARGET_VALUE,
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
import { STATEMENTS_PROPERTY_EXISTS } from '@/store/entity/statements/getterTypes';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import DataValue from '@/datamodel/DataValue';
import { action, getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import Term from '@/datamodel/Term';
import WikibaseRepoConfigRepository from '@/definitions/data-access/WikibaseRepoConfigRepository';
import validateBridgeApplicability from '@/store/validateBridgeApplicability';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';
import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';
import { BridgePermissionsRepository } from '@/definitions/data-access/BridgePermissionsRepository';

function commitErrors( context: ActionContext<Application, Application>, errors: ApplicationError[] ): void {
	context.commit( APPLICATION_ERRORS_ADD, errors );
}

function validateEntityState(
	context: ActionContext<Application, Application>,
	path: MainSnakPath,
): void {
	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS )
		]( path.entityId, path.propertyId ) === false
	) {
		commitErrors( context, [ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ] );
		return;
	}

	validateBridgeApplicability( context, path );
}

export default function actions(
	entityLabelRepository: EntityLabelRepository,
	wikibaseRepoConfigRepository: WikibaseRepoConfigRepository,
	propertyDatatypeRepository: PropertyDatatypeRepository,
	tracker: BridgeTracker,
	editAuthorizationChecker: BridgePermissionsRepository,
): ActionTree<Application, Application> {
	return {

		[ BRIDGE_INIT ](
			context: ActionContext<Application, Application>,
			information: AppInformation,
		): Promise<void> {
			context.commit( EDITFLOW_SET, information.editFlow );
			context.commit( PROPERTY_TARGET_SET, information.propertyId );
			context.commit( ENTITY_TITLE_SET, information.entityTitle );
			context.commit( ORIGINAL_HREF_SET, information.originalHref );
			context.commit( PAGE_TITLE_SET, information.pageTitle );

			entityLabelRepository.getLabel( information.propertyId )
				.then( ( label: Term ) => {
					context.commit( TARGET_LABEL_SET, label );
				}, ( _error ) => {
				// TODO: handling on failed label loading, which is not a bocking error for now
				} );

			return Promise.all( [
				wikibaseRepoConfigRepository.getRepoConfiguration(),
				editAuthorizationChecker.canUseBridgeForItemAndPage(
					information.entityTitle,
					information.pageTitle,
				),
				propertyDatatypeRepository.getDataType( information.propertyId ),
				context.dispatch(
					action( NS_ENTITY, ENTITY_INIT ),
					{ entity: information.entityId },
				),
			] ).then( ( [ wikibaseRepoConfiguration, permissionErrors, dataType, _entityInit ] ) => {

				if ( permissionErrors.length ) {
					commitErrors( context, permissionErrors );
					return;
				}

				tracker.trackPropertyDatatype( dataType );

				BridgeConfig( Vue, { ...wikibaseRepoConfiguration, ...information.client } );
				const state = context.state as InitializedApplicationState;
				const path = {
					entityId: state[ NS_ENTITY ].id,
					propertyId: state.targetProperty,
					index: 0,
				};

				validateEntityState( context, path );
				if ( context.getters.applicationStatus !== ApplicationStatus.ERROR ) {
					context.commit(
						ORIGINAL_STATEMENT_SET,
						state[ NS_ENTITY ][ NS_STATEMENTS ][ path.entityId ][ path.propertyId ][ path.index ],
					);

					context.commit(
						APPLICATION_STATUS_SET,
						ApplicationStatus.READY,
					);
				}
			}, ( error ) => {
				commitErrors( context, [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: error } ] );
				// TODO: store information about the error somewhere and show it!
				throw error;
			} );
		},

		[ BRIDGE_SET_TARGET_VALUE ](
			context: ActionContext<Application, Application>,
			dataValue: DataValue,
		): Promise<void> {
			if ( context.state.applicationStatus !== ApplicationStatus.READY ) {
				commitErrors( context, [ {
					type: ErrorTypes.APPLICATION_LOGIC_ERROR,
					info: { stack: ( new Error() ).stack },
				} ] );
				return Promise.reject( null );
			}

			const state = context.state as InitializedApplicationState;
			const path = {
				entityId: state[ NS_ENTITY ].id,
				propertyId: state.targetProperty,
				index: 0,
			};

			return context.dispatch(
				action(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakActionTypes.setStringDataValue,
				), {
					path,
					value: dataValue,
				},
			).catch( ( error ) => {
				commitErrors( context, [ {
					type: ErrorTypes.APPLICATION_LOGIC_ERROR,
					info: error,
				} ] );
				// TODO: store information about the error somewhere and show it!
				throw error;
			} );
		},

		[ BRIDGE_SAVE ](
			context: ActionContext<Application, Application>,
		): Promise<void> {
			if ( context.state.applicationStatus !== ApplicationStatus.READY ) {
				commitErrors( context, [ {
					type: ErrorTypes.APPLICATION_LOGIC_ERROR,
					info: { stack: ( new Error() ).stack },
				} ] );
				return Promise.reject( null );
			}

			return context.dispatch(
				action( NS_ENTITY, ENTITY_SAVE ),
			)
				.catch( ( error: Error ) => {
					commitErrors( context, [ { type: ErrorTypes.SAVING_FAILED, info: error } ] );
					// TODO: store information about the error somewhere and show it!
					throw error;
				} );
		},

		[ BRIDGE_ERROR_ADD ](
			context: ActionContext<Application, Application>,
			errors: ApplicationError[],
		): void {
			commitErrors( context, errors );
		},

		[ BRIDGE_SET_EDIT_DECISION ](
			context: ActionContext<Application, Application>,
			editDecision: EditDecision,
		): void {
			return context.commit( EDITDECISION_SET, editDecision );
		},
	};
}
