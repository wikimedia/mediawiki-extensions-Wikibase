import {
	NS_ENTITY,
} from './namespaces';
import EntityState from '@/store/entity/EntityState';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import Term from '@/datamodel/Term';

interface Application {
	editFlow: string;
	targetProperty: string;
	targetLabel: Term|null;
	applicationStatus: ApplicationStatus;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: EntityState;
}
