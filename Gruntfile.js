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
		tmp: [],

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

		tx_contributors: {
			"content-aware-sidebars": {
				options: {
					templateFn: function(array) {
						for(var i = 0; i < array.length; i++) {
							array[i] = "["+array[i]+"](https://www.transifex.com/accounts/profile/"+array[i]+"/)";
						}
						return array.join(", ");
					}
				}
			}
		},

		replace: {
			readme: {
				src: ['readme.txt'],
				overwrite: true,
				replacements: [{
					from: /(\*{8}\n)([\S\s])*?(\n\*{8})/g,
					to: "$1<%= tx_contributors %>$3"
				}]
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
				files: 'css/*.less',
				tasks: ['less']
			},
			js: {
				files: ['js/*.js','!js/*.min.js'],
				tasks: ['uglify']
			}
		}
	});

	/**
	 * Register tasks
	 */
	grunt.registerTask('localize', ['transifex','potomo']);
	grunt.registerTask('localize-contrib',['tx_contributors','replace:readme']);
	grunt.registerTask('default', ['build']);
	grunt.registerTask('build', ['less','uglify']);
	grunt.registerTask('deploy', ['build','localize']);

};