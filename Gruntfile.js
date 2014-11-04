module.exports = function(grunt) {

	/**
	 * Load tasks
	 */
	require('load-grunt-tasks')(grunt);

	/**
	 * Configuration
	 */
	grunt.initConfig({

		/**
		 * Load parameters
		 */
		pkg: grunt.file.readJSON('package.json'),

		/**
		 * Compile css
		 */
		less: {
			development: {
				options: {
					paths: ["css"],
					cleancss: true,
				},
				files: {
					"css/style.css": "css/style.less"
				}
			}
		},

		uglify: {
			options: {
				preserveComments: 'some',
				compress: {
					drop_console: true
				},
				mangle: {
					except: ['jQuery', 'CASAdmin']
				}
			},
			my_target: {
				files: [{
					'js/cas_admin.min.js': ['js/cas_admin.js'],
					'js/widgets.min.js': ['js/widgets.js']
				}]
			}
		},

		/**
		 * Get .po files from Transifex project
		 */
		transifex: {
			"content-aware-sidebars": {
				options: {
					targetDir: "./lang",
					mode: "file",
					filename : "_resource_-_lang_.po"
				}
			}
		},

		/**
		 * Compile po files
		 */
		potomo: {
			dist: {
				options: {
					poDel: false
				},
				files: [{
					expand: true,
					cwd: './lang',
					src: ['*.po'],
					dest: './lang',
					ext: '.mo',
					nonull: true
				}]
			}
		},

		watch: {
			css: {
				files: '**/style.less',
				tasks: ['less']
			}
		}
	});

	/**
	 * Register tasks
	 */
	grunt.registerTask('localize', ['transifex','potomo']);
	grunt.registerTask('default', ['build']);
	grunt.registerTask('build', ['less','uglify']);
	grunt.registerTask('deploy', ['build','localize']);

};