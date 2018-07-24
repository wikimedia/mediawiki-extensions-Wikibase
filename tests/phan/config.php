<?php


// If xdebug is enabled, we need to increase the nesting level for phan
ini_set( 'xdebug.max_nesting_level', 1000 );

/**
 * This configuration will be read and overlayed on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 *
 * @see src/Phan/Config.php
 * See Config for all configurable options.
 *
 * A Note About Paths
 * ==================
 *
 * Files referenced from this file should be defined as
 *
 * ```
 *   Config::projectPath('relative_path/to/file')
 * ```
 *
 * where the relative path is relative to the root of the
 * project which is defined as either the working directory
 * of the phan executable or a path passed in via the CLI
 * '-d' flag.
 */
return [
	/**
	 * A list of individual files to include in analysis
	 * with a path relative to the root directory of the
	 * project. directory_list won't find .inc files so
	 * we augment it here.
	 */
	'file_list' => [
		'client/WikibaseClient.datatypes.php',
		'client/ClientHooks.php',
		'client/WikibaseClient.i18n.alias.php',
		'client/WikibaseClient.i18n.magic.php',
		'client/WikibaseClient.php',
		'lib/config/WikibaseLib.default.php',
		'lib/WikibaseLib.datatypes.php',
		'lib/WikibaseLib.entitytypes.php',
		'lib/LibHooks.php',
		'lib/WikibaseLib.php',
		'repo/config/Wikibase.default.php',
		'repo/config/Wikibase.searchindex.php',
		'repo/RepoHooks.php',
		'repo/Wikibase.i18n.alias.php',
		'repo/Wikibase.i18n.namespaces.php',
		'repo/Wikibase.php',
		'repo/WikibaseRepo.datatypes.php',
		'repo/WikibaseRepo.entitytypes.php',
		'view/resources.php',
		'view/WikibaseView.php',
		'Wikibase.php',
		// Include extension stubs so we don't require extensions to be available locally.
		'tests/phan/stubs/babel.php',
		'tests/phan/stubs/cirrussearch.php',
		'tests/phan/stubs/echo.php',
		'tests/phan/stubs/geodata.php',
		'tests/phan/stubs/math.php',
		'tests/phan/stubs/pageimages.php',
		'tests/phan/stubs/scribunto.php',
	],

	/**
	 * A list of directories that should be parsed for class and
	 * method information. After excluding the directories
	 * defined in exclude_analysis_directory_list, the remaining
	 * files will be statically analyzed for errors.
	 *
	 * Thus, both first-party and third-party code being used by
	 * your application should be included in this list.
	 */
	'directory_list' => array_merge(
		[
			'data-access/src',
			'client/includes',
			'repo/includes',
			'lib/includes',
			'client/maintenance',
			'repo/maintenance',
			'lib/maintenance',
			'view/src',
			'./../../includes',
			'./../../languages',
			'./../../maintenance',
			'./../../vendor',
		],
		// "Local" vendor dir not needed when all dependencies are merged in core's vendor dir.
		is_dir( 'vendor' ) ? [ 'vendor' ] : []
	),

	/**
	 * A file list that defines files that will be excluded
	 * from parsing and analysis and will not be read at all.
	 *
	 * This is useful for excluding hopelessly unanalyzable
	 * files that can't be removed for whatever reason.
	 */
	'exclude_file_list' => [],

	/**
	 * A list of directories holding code that we want
	 * to parse, but not analyze. Also works for individual
	 * files.
	 */
	"exclude_analysis_directory_list" => array_merge(
		[
			'tests/phan/stubs',
			'./../../includes',
			'./../../languages',
			'./../../maintenance',
			'./../../vendor',
			'./../../extensions',
		],
		// "Local" vendor dir not needed when all dependencies are merged in core's vendor dir.
		is_dir( 'vendor' ) ? [ 'vendor' ] : []
	),

	/**
	 * Backwards Compatibility Checking. This is slow
	 * and expensive, but you should consider running
	 * it before upgrading your version of PHP to a
	 * new version that has backward compatibility
	 * breaks.
	 */
	'backward_compatibility_checks' => false,

	/**
	 * A set of fully qualified class-names for which
	 * a call to parent::__construct() is required
	 */
	'parent_constructor_required' => [
	],

	/**
	 * Run a quick version of checks that takes less
	 * time at the cost of not running as thorough
	 * an analysis. You should consider setting this
	 * to true only when you wish you had more issues
	 * to fix in your code base.
	 *
	 * In quick-mode the scanner doesn't rescan a function
	 * or a method's code block every time a call is seen.
	 * This means that the problem here won't be detected:
	 *
	 * ```php
	 * <?php
	 * function test($arg):int {
	 * 	return $arg;
	 * }
	 * test("abc");
	 * ```
	 *
	 * This would normally generate:
	 *
	 * ```sh
	 * test.php:3 TypeError return string but `test()` is declared to return int
	 * ```
	 *
	 * The initial scan of the function's code block has no
	 * type information for `$arg`. It isn't until we see
	 * the call and rescan test()'s code block that we can
	 * detect that it is actually returning the passed in
	 * `string` instead of an `int` as declared.
	 */
	'quick_mode' => false,

	/**
	 * By default, Phan will not analyze all node types
	 * in order to save time. If this config is set to true,
	 * Phan will dig deeper into the AST tree and do an
	 * analysis on all nodes, possibly finding more issues.
	 *
	 * See \Phan\Analysis::shouldVisit for the set of skipped
	 * nodes.
	 */
	'should_visit_all_nodes' => true,

	/**
	 * If enabled, check all methods that override a
	 * parent method to make sure its signature is
	 * compatible with the parent's. This check
	 * can add quite a bit of time to the analysis.
	 */
	'analyze_signature_compatibility' => true,

	// Emit all issues. They are then suppressed via
	// suppress_issue_types, rather than a minimum
	// severity.
	"minimum_severity" => 0,

	/**
	 * If true, missing properties will be created when
	 * they are first seen. If false, we'll report an
	 * error message if there is an attempt to write
	 * to a class property that wasn't explicitly
	 * defined.
	 */
	'allow_missing_properties' => false,

	/**
	 * Allow null to be cast as any type and for any
	 * type to be cast to null. Setting this to false
	 * will cut down on false positives.
	 */
	'null_casts_as_any_type' => true,

	/**
	 * If enabled, scalars (int, float, bool, string, null)
	 * are treated as if they can cast to each other.
	 *
	 * MediaWiki is pretty lax and uses many scalar
	 * types interchangably.
	 */
	'scalar_implicit_cast' => true,

	/**
	 * If true, seemingly undeclared variables in the global
	 * scope will be ignored. This is useful for projects
	 * with complicated cross-file globals that you have no
	 * hope of fixing.
	 */
	'ignore_undeclared_variables_in_global_scope' => false,

	/**
	 * Set to true in order to attempt to detect dead
	 * (unreferenced) code. Keep in mind that the
	 * results will only be a guess given that classes,
	 * properties, constants and methods can be referenced
	 * as variables (like `$class->$property` or
	 * `$class->$method()`) in ways that we're unable
	 * to make sense of.
	 */
	'dead_code_detection' => false,

	/**
	 * If true, the dead code detection rig will
	 * prefer false negatives (not report dead code) to
	 * false positives (report dead code that is not
	 * actually dead) which is to say that the graph of
	 * references will create too many edges rather than
	 * too few edges when guesses have to be made about
	 * what references what.
	 */
	'dead_code_detection_prefer_false_negative' => true,

	/**
	 * If disabled, Phan will not read docblock type
	 * annotation comments (such as for @return, @param,
	 * @var, @suppress, @deprecated) and only rely on
	 * types expressed in code.
	 */
	'read_type_annotations' => true,

	/**
	 * If a file path is given, the code base will be
	 * read from and written to the given location in
	 * order to attempt to save some work from being
	 * done. Only changed files will get analyzed if
	 * the file is read
	 */
	'stored_state_file_path' => null,

	/**
	 * Set to true in order to ignore issue suppression.
	 * This is useful for testing the state of your code, but
	 * unlikely to be useful outside of that.
	 */
	'disable_suppression' => false,

	/**
	 * If set to true, we'll dump the AST instead of
	 * analyzing files
	 */
	'dump_ast' => false,

	/**
	 * If set to a string, we'll dump the fully qualified lowercase
	 * function and method signatures instead of analyzing files.
	 */
	'dump_signatures_file' => null,

	/**
	 * If true (and if stored_state_file_path is set) we'll
	 * look at the list of files passed in and expand the list
	 * to include files that depend on the given files
	 */
	'expand_file_list' => false,

	// Include a progress bar in the output
	'progress_bar' => false,

	/**
	 * The probability of actually emitting any progress
	 * bar update. Setting this to something very low
	 * is good for reducing network IO and filling up
	 * your terminal's buffer when running phan on a
	 * remote host.
	 */
	'progress_bar_sample_rate' => 0.005,

	/**
	 * The number of processes to fork off during the analysis
	 * phase.
	 */
	'processes' => 1,

	/**
	 * Add any issue types (such as 'PhanUndeclaredMethod')
	 * to this black-list to inhibit them from being reported.
	 */
	'suppress_issue_types' => [
		// approximate error count: 6
		"PhanDeprecatedClass",
		// approximate error count: 107
		"PhanDeprecatedFunction",
		// approximate error count: 3
		"PhanParamReqAfterOpt",
		// approximate error count: 9
		"PhanParamSignatureMismatch",
		// approximate error count: 1
		"PhanParamSignatureMismatchInternal",
		// approximate error count: 4
		"PhanParamTooMany",
		// approximate error count: 2
		"PhanTypeComparisonToArray",
		// approximate error count: 47
		"PhanTypeMismatchArgument",
		// approximate error count: 5
		"PhanTypeMismatchArgumentInternal",
		// approximate error count: 3
		"PhanTypeMismatchProperty",
		// approximate error count: 2
		"PhanTypeMismatchReturn",
		// approximate error count: 1
		"PhanTypeMissingReturn",
		// approximate error count: 72
		"PhanUndeclaredConstant",
		// approximate error count: 168
		"PhanUndeclaredMethod",
		// approximate error count: 17
		"PhanUndeclaredProperty",
		// approximate error count: 4
		"PhanUndeclaredVariable",
	],

	/**
	 * If empty, no filter against issues types will be applied.
	 * If this white-list is non-empty, only issues within the list
	 * will be emitted by Phan.
	 */
	'whitelist_issue_types' => [
	],

	/**
	 * Override to hardcode existence and types of (non-builtin) globals in the global scope.
	 * Class names must be prefixed with '\\'.
	 * (E.g. ['_FOO' => '\\FooClass', 'page' => '\\PageClass', 'userId' => 'int'])
	 */
	'globals_type_map' => [
	],

	// Emit issue messages with markdown formatting
	'markdown_issue_messages' => false,

	/**
	 * Enable or disable support for generic templated
	 * class types.
	 */
	'generic_types_enabled' => true,

	// A list of plugin files to execute
	'plugins' => [
	],
];
