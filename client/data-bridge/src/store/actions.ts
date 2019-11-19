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
	BRIDGE_SET_TARGET_VALUE,
} from '@/store/actionTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import AppInformation from '@/definitions/AppInformation';
import {
	APPLICATION_ERRORS_ADD,
	APPLICATION_STATUS_SET,
	EDITFLOW_SET,
	PROPERTY_TARGET_SET,
	TARGET_LABEL_SET,
	ORIGINAL_STATEMENT_SET,
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

function validateEntityState(
	context: ActionContext<Application, Application>,
	path: MainSnakPath,
): boolean {
	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS )
		]( path.entityId, path.propertyId ) === false
	) {
		return false;
	}

	return validateBridgeApplicability( context, path );
}

function commitErrors( context: ActionContext<Application, Application>, errors: ApplicationError[] ): void {
	context.commit( APPLICATION_ERRORS_ADD, errors );
}

export default function actions(
	entityLabelRepository: EntityLabelRepository,
	wikibaseRepoConfigRepository: WikibaseRepoConfigRepository,
): ActionTree<Application, Application> {
	return {
		[ BRIDGE_INIT ](
			context: ActionContext<Application, Application>,
			information: AppInformation,
		): Promise<void> {
			context.commit( EDITFLOW_SET, information.editFlow );
			context.commit( PROPERTY_TARGET_SET, information.propertyId );

			entityLabelRepository.getLabel( information.propertyId )
				.then( ( label: Term ) => {
					context.commit( TARGET_LABEL_SET, label );
				}, ( _error ) => {
				// TODO: handling on failed label loading, which is not a bocking error for now
				} );

			return Promise.all( [
				wikibaseRepoConfigRepository.getRepoConfiguration(),
				context.dispatch(
					action( NS_ENTITY, ENTITY_INIT ),
					{ entity: information.entityId },
				),
			] ).then( ( [ wikibaseRepoConfiguration, _entityInit ] ) => {

				BridgeConfig( Vue, { ...wikibaseRepoConfiguration, ...information.client } );
				const state = context.state as InitializedApplicationState;
				const path = {
					entityId: state[ NS_ENTITY ].id,
					propertyId: state.targetProperty,
					index: 0,
				};

				if ( validateEntityState( context, path ) ) {
					context.commit(
						ORIGINAL_STATEMENT_SET,
						state[ NS_ENTITY ][ NS_STATEMENTS ][ path.entityId ][ path.propertyId ][ path.index ],
					);

					context.commit(
						APPLICATION_STATUS_SET,
						ApplicationStatus.READY,
					);
				} else {
					// TODO: somehow store *why* we cannot edit this
					commitErrors( context, [ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ] );
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
	};
}
