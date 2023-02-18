var args = process.argv.slice(2);
var action = (args[0] || '');

var cssFiles = [
  'node_modules/bootstrap/dist/css/bootstrap.css',
  'node_modules/datetimepicker/dist/DateTimePicker.css',
  'node_modules/bootstrap-select/dist/css/bootstrap-select.css',
  'node_modules/filepond/dist/filepond.css',
  'node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css',
  'node_modules/@fortawesome/fontawesome-free/css/all.css',
];

var jsFiles = [
  "node_modules/lodash/lodash.js",
  "node_modules/jquery/dist/jquery.js",
  "node_modules/bootstrap/dist/js/bootstrap.bundle.js",
  "node_modules/bootstrap-cookie-alert/cookiealert.js",
  "node_modules/datetimepicker/dist/DateTimePicker.js",
  "node_modules/bootstrap-select/dist/js/bootstrap-select.js",
  "node_modules/filepond/dist/filepond.js",
  "node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js",
  "node_modules/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js",
  "node_modules/fullcalendar/index.global.js",
  "node_modules/@fullcalendar/core/locales-all.global.js",
  "node_modules/@fullcalendar/bootstrap5/index.global.js"
]

// pliki JS aplikacyjne - kolejność jest istotna
var jsAppFiles = [
  'node_modules/serwisant-cp/js-libs/purl.js',
  'node_modules/serwisant-cp/js-libs/password_strength.js',
  'node_modules/serwisant-cp/js-libs/jquery_strength.js',
  'node_modules/serwisant-cp/js-libs/application.js',
  'node_modules/serwisant-cp/js-libs/application_ui.js',
  'node_modules/serwisant-cp/js-libs/layout.js',
]

const BuildFunctions = require('./build-functions');

// font-awesome
BuildFunctions.mkdir('public/webfonts')

if (action == '') {
  BuildFunctions.withFiles('node_modules/@fortawesome/fontawesome-free/webfonts', function (file) {
    BuildFunctions.copy(file, 'public/webfonts')
  })
}

BuildFunctions.mkdir('public/assets-serwisant-cp')

// pliki css
if (action == '') {
  BuildFunctions.compressCSS(cssFiles, 'public/assets-serwisant-cp/serwisant-cp-vendor.css');
}
if (action == '' || action == 'watch') {
  BuildFunctions.compressCSSdir('node_modules/serwisant-cp/css', 'public/assets-serwisant-cp/serwisant-cp.css')
}

// pliki JS (pojedyncze pliki związane z kontrollerami są ładowane on-demand)
if (action == '') {
  BuildFunctions.compressJS(jsFiles, 'public/assets-serwisant-cp/serwisant-cp-vendor.js')
}

if (action == '' || action == 'watch') {
  BuildFunctions.compressJS(jsAppFiles, 'public/assets-serwisant-cp/serwisant-cp.js')
  BuildFunctions.withFiles('node_modules/serwisant-cp/js', function (file) {
    BuildFunctions.copy(file, 'public/assets-serwisant-cp')
  })
}
// obrazki
if (action == '') {
  BuildFunctions.withFiles('node_modules/serwisant-cp/img', function (file) {
    BuildFunctions.copy(file, 'public/assets-serwisant-cp')
  })
}
