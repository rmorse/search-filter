// Search & Filter

/* ==========
 * REQUIRE
 * ========== */
var gulp			= require('gulp');
var plugins 		= require('gulp-load-plugins')(); //auto load `gulp
var rename 			= require('gulp-rename'); //change filename before saving
var del 			= require('del'); //for cleaning up the folder on rebuild
var fs 				= require('fs'); //for cleaning up the folder on rebuild
var path 			= require('path');

var handleErrors	= require('./gulp/handleErrors');


/* ==========
 * CONFIG
 * ========== */
var projectDefaults = {
	name: 	'search-filter-free',
	build:	 './build',
	publicApp: {},
	src: 	'src/',
	dev: 	'dist/',
	dist: 	'dist/'
};
let project = projectDefaults;

const localBuildConfigPath = './local/config.js';
if ( fs.existsSync( localBuildConfigPath ) ) {
	// We have a local config we want to merge
	const localBuildConfig = require( localBuildConfigPath );
	project = { ...projectDefaults, ...localBuildConfig };
}

project.publicApp = {
	src: './'+project.src+'/public/assets/js/',
	cssSrc: './'+project.src+'/public/assets/css/',

	build: project.build+'/public/assets/js/',
    cssBuild: project.build+'/public/assets/css/',

	dist: './'+project.dev+'/public/assets/js/'
}


var appTasks = new Array(); //contain references to dynamically generated app tasks - app tasks are tasks which are specific to a particular game
appTasks['scripts']  = new Array();
appTasks['templates']  = new Array();

// config for tasks & modules 
var options = {
	uglify: { 
		compress: {
			drop_console: true //always remove console.logs - dist should never have console.logs
		},
		mangle: true
	},
	minifyCss: {
		//keepBreaks: true,
		compatibility: 'ie8',
		keepSpecialCommentsBreaks: true
	},
}

/* ==========
 * TASKS
 * ========== */

gulp.task('copy-plugin', function ( done ) {
	gulp.src([
		project.src+'**/*',
		'!'+project.publicApp.src+"**/*",
		'!'+project.publicApp.cssSrc+"**/*"
		
	]).pipe(plugins.newer(project.build)).pipe(gulp.dest(project.build));
	done();
});

function ensureDirectoryExistence(filePath) {
	var dirname = path.dirname(filePath);
	if (fs.existsSync(dirname)) {
	  return true;
	}
	ensureDirectoryExistence(dirname);
	fs.mkdirSync(dirname);
}


/* 
 *	Utility functions 
 */
gulp.task('clean', function (cb) {
  del([
		project.build+'**/*.*',
		project.dev+'**/*.*',
		project.build+'**',
		project.dev+'**'
	], cb);
});

gulp.task('watch', function( done ) {
	//wait for changes and run tasks accordingly
	gulp.watch([
		project.src+'**/*',
	], gulp.series('copy-plugin') );
	
	done();
});


gulp.task('default', gulp.series( 'copy-plugin', 'watch', function( done ) {
	console.log("Init main tasks");
	done();
} ) );
