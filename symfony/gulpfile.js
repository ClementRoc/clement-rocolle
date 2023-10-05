'use strict';

var gulp = require('gulp'),
    newer = require('gulp-newer'),
    merge = require('merge-stream'),
    concat = require('gulp-concat'),
    babel = require('gulp-babel'),
    plumber = require('gulp-plumber'),
    addSrc = require('gulp-add-src'),
    gutil = require('gulp-util'),
    cleanCSS = require('gulp-clean-css'),
    autoprefixer = require('gulp-autoprefixer'),
    process = require('process'),
    webp = require('gulp-webp'),
    rename = require('gulp-rename')

const sass = require('gulp-sass')(require('sass'));

var minifyIfNeeded = function (alwaysMinify) {
    return alwaysMinify === true || gutil.env.env === 'prod' || gutil.env.env === 'preprod' ? cleanCSS({processImport: false}) : gutil.noop();
}

var uglifyIfNeeded = function(alwaysUglify) {
    return alwaysUglify === true || gutil.env.env === 'prod' || gutil.env.env === 'preprod' ? uglify() : gutil.noop();
}

var errorHandler = function (err) {
    if (typeof err.file !== 'undefined') {
        console.log(err.file, err.line, err.messageOriginal);
    } else {
        console.log(err);
    }
    this.emit('end');
}

var paths = {
    src: './assets/',
    dest: './public/build/',
    imgDest: './public/build/img',
    vendor: 'vendor/',
    npm: 'node_modules/'
};

gulp.task('js', function () {
    return gulp.src(paths.src + 'js/**/*.*')
        .pipe(plumber({ errorHandler: errorHandler }))
        .pipe(uglifyIfNeeded())
        .pipe(concat('app.js'))
        .pipe(gulp.dest(paths.dest + 'js'));
});

gulp.task('css', function () {
    return gulp.src(paths.src + 'scss/main.scss')
        .pipe(plumber({ errorHandler: errorHandler }))
        .pipe(sass())
        .pipe(minifyIfNeeded(true))
        .pipe(concat('app.css'))
        .pipe(gulp.dest(paths.dest + 'css'));
});

gulp.task('css-fonts', function () {
    return gulp.src(paths.src + 'scss/fonts.scss')
        .pipe(plumber({ errorHandler: errorHandler }))
/*        .pipe(autoprefixer({ browsers: ['last 2 versions'] }))*/
        .pipe(minifyIfNeeded(true))
        .pipe(concat('fonts.css'))
        .pipe(gulp.dest(paths.dest + 'css'));
});

gulp.task('webp', function() {
    return gulp.src([paths.src + 'img/**/*.{jpg,jpeg,png}'])
        .pipe(rename({
            extname: '.webp',
        }))
        .pipe(webp({ quality: 80 }))
        .pipe(gulp.dest(paths.imgDest));
});

gulp.task('image', function () {
    var optimExcludeFolders = ['img/design/devices'];
    var optimTask = gulp.src([
        paths.src + 'img/**/*.{jpg,jpeg,png,gif,svg,ico}',
        ...optimExcludeFolders.map(f => '!' + paths.src + 'img/' + f + '/**/*.{jpg,jpeg,png,gif,svg,ico}')
    ])
        .pipe(newer(paths.imgDest))
        .pipe(gulp.dest(paths.dest + 'img'));
    var copyTasks = optimExcludeFolders.map(f =>
        gulp.src(paths.src + f + '/**/*.{jpg,jpeg,png,gif,svg,ico}')
            .pipe(newer(paths.dest + f))
            .pipe(gulp.dest(paths.dest + f)));
    return merge([optimTask, copyTasks]);
});

gulp.task('fonts', function() {
    return gulp.src(paths.src + 'fonts/*')
        .pipe(gulp.dest(paths.dest + 'fonts'));
});

// Watch task
gulp.task('watch', function() {
    gulp.watch([paths.src + 'scss/**/*.scss'], gulp.series(['css']));
    gulp.watch([paths.src + 'js/**/*.*'], gulp.series(['js']));
});

// Force to stop at CTRL + C
process.on('SIGINT', function() {
    process.exit();
});

exports.default = gulp.series(['css', 'webp', 'image', 'js']);
