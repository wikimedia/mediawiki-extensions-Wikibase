interface Application {
	editFlow: string;
	targetProperty: string;
}

export default Application;

export interface InitializedApplicationState extends Application {}
