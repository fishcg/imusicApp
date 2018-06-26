var path = require('path');
var gulp = require('gulp');
var uglify = require('gulp-uglify');

var rootPath = path.join(__dirname, '../..')
var publicFolder = rootPath + '/public'

gulp.task('default', function () {
  var js_path = path.join(publicFolder, '/static/admin/js/ruchong.js')
  var min_js_path = path.join(publicFolder, 'js/mini')
  gulp.src(js_path).pipe(uglify()).pipe(gulp.dest(min_js_path))
});