var gulp = require('gulp'),
	less = require('gulp-less'),
	uglify = require('gulp-uglify'),
	rename = require("gulp-rename");

gulp.task('less', function (done) {
	return gulp.src('css/style.less')
		.pipe(less({
			plugins: [
				new (require('less-plugin-autoprefix'))({ browsers: ['last 2 versions'] }),
				new (require('less-plugin-clean-css'))({advanced:true})
			]
		}))
		.pipe(gulp.dest('css'));
});

gulp.task('uglify', function (cb) {
	return gulp.src(['js/*.js','!js/*.min.js'])
		.pipe(uglify({
			compress: {
				drop_console: true
			},
			mangle: {
				reserved: ['jQuery', 'CASAdmin','$']
			},
			output: {
				comments: 'some'
			},
			warnings: false
		}))
		.pipe(rename({extname: '.min.js'}))
		.pipe(gulp.dest('js'));
});

gulp.task('watch', function() {
	gulp.watch('css/style.less', gulp.parallel('less'));
	gulp.watch(['js/*.js','!js/*.min.js'], gulp.parallel('uglify'));
});

gulp.task('build', gulp.parallel('less','uglify'));

gulp.task('default', gulp.parallel('build'));

