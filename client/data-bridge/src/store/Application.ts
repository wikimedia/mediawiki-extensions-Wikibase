import {
	NS_ENTITY,
} from './namespaces';
import { InitializedEntityState } from '@/store/entity/EntityState';
import ApplicationStatus from '@/definitions/ApplicationStatus';

interface Application {
	editFlow: string;
	targetProperty: string;
	applicationStatus: ApplicationStatus;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: InitializedEntityState;
}
