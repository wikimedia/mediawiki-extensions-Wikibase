import {
	NS_ENTITY,
} from './namespaces';
import EntityState from '@/store/entity/EntityState';
import ApplicationStatus from '@/store/ApplicationStatus';

interface Application {
	editFlow: string;
	targetProperty: string;
	applicationStatus: ApplicationStatus;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: EntityState;
}
