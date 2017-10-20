/**
 * @file
 */

'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var gutil = require('gulp-util');

var config = {
  'sassDirectories': [
    { src: './modules/keypad/sass/**/*.scss', dest: './modules/keypad/css'},
    { src: './sass/**/*.scss', dest: './css' }
  ]
};

gulp.task('sass', function () {
  config.sassDirectories.map(function (dirInfo) {
    gulp.src(dirInfo.src)
      .pipe(sourcemaps.init())
      .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
      .pipe(sourcemaps.write('./'))
      .pipe(gulp.dest(dirInfo.dest))
  });
});

gulp.task('sass:watch', function () {
  config.sassDirectories.map(function (dirInfo) {
    gulp.watch(dirInfo.src, ['sass']);
  });
});

gulp.task('build', function () {
  gulp.start(['sass']);
});

gulp.task('watch', function () {
  gulp.start(['sass:watch']);
});
