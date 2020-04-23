declare module 'cssjanus' {
	function transform(
		css: string,
		options?: {
			transformDirInUrl?: boolean;
			transformEdgeInUrl?: boolean;
		},
	): string;
	function transform(
		css: string,
		transformDirInUrl?: boolean,
		transformEdgeInUrl?: boolean,
	): string;
}
