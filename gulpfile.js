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
    { src: './modules/cashier/sass/**/*.scss', dest: './modules/cashier/css'},
    { src: './modules/cashier/sass/**/*.scss', dest: './modules/cashier/css'},
    { src: './modules/currency_denomination/sass/**/*.scss', dest: './modules/currency_denomination/css'},
    { src: './modules/keypad/sass/**/*.scss', dest: './modules/keypad/css'},
    { src: './modules/label/sass/**/*.scss', dest: './modules/label/css'},
    { src: './modules/report/sass/**/*.scss', dest: './modules/report/css'},
    { src: './modules/receipt/sass/**/*.scss', dest: './modules/receipt/css'},
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
