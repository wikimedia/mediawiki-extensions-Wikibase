import {
	ActionContext,
	ActionTree,
} from 'vuex';
import Application from '@/store/Application';
import {
	BRIDGE_INIT,
	BRIDGE_SAVE,
	BRIDGE_SET_TARGET_VALUE,
} from '@/store/actionTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import AppInformation from '@/definitions/AppInformation';
import {
	APPLICATION_STATUS_SET,
	EDITFLOW_SET,
	PROPERTY_TARGET_SET,
	TARGET_LABEL_SET,
} from '@/store/mutationTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import {
	ENTITY_ID,
} from '@/store/entity/getterTypes';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import DataValue from '@/datamodel/DataValue';
import { action, getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import Term from '@/datamodel/Term';

export default function actions(
	entityLabelRepository: EntityLabelRepository,
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

			return context.dispatch(
				action( NS_ENTITY, ENTITY_INIT ),
				{ entity: information.entityId },
			).then( () => {
				const entityId = context.getters[ getter( NS_ENTITY, ENTITY_ID ) ];
				const path = {
					entityId,
					propertyId: context.state.targetProperty,
					index: 0,
				};

				if ( context.getters[
					getter( NS_ENTITY, NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS )
				]( entityId, context.state.targetProperty ) === false
				) {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
					// TODO: store information about the error somewhere and show it!
				} else if ( context.getters[
					getter( NS_ENTITY, NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS )
				]( entityId, context.state.targetProperty ) === true
				) {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
					// TODO: store information about the error somewhere and show it!
				} else if ( context.getters[
					getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
				]( path ) !== 'value'
				) {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
					// TODO: store information about the error somewhere and show it!
				} else if ( context.getters[
					getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
				]( path ) !== 'string'
				) {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
					// TODO: store information about the error somewhere and show it!
				} else {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
				}
			}, ( error ) => {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				// TODO: store information about the error somewhere and show it!
				throw error;
			} );
		},

		[ BRIDGE_SET_TARGET_VALUE ](
			context: ActionContext<Application, Application>,
			dataValue: DataValue,
		): Promise<void> {
			if ( context.state.applicationStatus !== ApplicationStatus.READY ) {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				return Promise.reject( null );
			}

			const entityId = context.getters[ getter( NS_ENTITY, ENTITY_ID ) ];
			const path = {
				entityId,
				propertyId: context.state.targetProperty,
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
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				// TODO: store information about the error somewhere and show it!
				throw error;
			} );
		},

		[ BRIDGE_SAVE ](
			context: ActionContext<Application, Application>,
		): Promise<void> {
			if ( context.state.applicationStatus !== ApplicationStatus.READY ) {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				return Promise.reject( null );
			}

			return context.dispatch(
				action( NS_ENTITY, ENTITY_SAVE ),
			)
				.catch( ( error: Error ) => {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
					// TODO: store information about the error somewhere and show it!
					throw error;
				} );
		},
	};
}
