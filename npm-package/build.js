console.log("Running postinstall for Serwisant Online customer panel SDK...\n");

var imgFiles = [
  'vendor/pragmarx/countries/src/data/flags/pol.svg',
  'vendor/pragmarx/countries/src/data/flags/gbr.svg'
]

var cssFiles = [
  'node_modules/bootstrap/dist/css/bootstrap.css',
  'node_modules/bootstrap-icons/font/bootstrap-icons.css',
  'node_modules/@eonasdan/tempus-dominus/dist/css/tempus-dominus.css',
  'node_modules/filepond/dist/filepond.css',
  'node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css',
  'node_modules/@fortawesome/fontawesome-free/css/all.css',
  'node_modules/select2/dist/css/select2.css',
  'node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.css',
];

var jsFiles = [
  "node_modules/lodash/lodash.js",
  "node_modules/jquery/dist/jquery.js",
  "node_modules/bootstrap/dist/js/bootstrap.bundle.js",
  "node_modules/bootstrap-cookie-alert/cookiealert.js",
  "node_modules/@popperjs/core/dist/umd/popper.js",
  "node_modules/@eonasdan/tempus-dominus/dist/js/tempus-dominus.js",
  "node_modules/@eonasdan/tempus-dominus/dist/js/jQuery-provider.js",
  "node_modules/filepond/dist/filepond.js",
  "node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js",
  "node_modules/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js",
  "node_modules/fullcalendar/index.global.js",
  "node_modules/@fullcalendar/core/locales-all.global.js",
  "node_modules/@fullcalendar/bootstrap5/index.global.js",
  "node_modules/select2/dist/js/select2.full.js",
  "node_modules/select2/dist/js/i18n/pl.js",
  "node_modules/select2/dist/js/i18n/en.js",
]

// kolejność jest istotna
var jsAppFiles = [
  'node_modules/serwisant-cp/js-libs/purl.js',
  'node_modules/serwisant-cp/js-libs/password_strength.js',
  'node_modules/serwisant-cp/js-libs/jquery_strength.js',
  'node_modules/serwisant-cp/js-libs/application.js',
  'node_modules/serwisant-cp/js-libs/application_ui.js',
  'node_modules/serwisant-cp/js-libs/layout.js',
]

const BuildFunctions = require('./build-functions');

BuildFunctions.mkdir('public/assets-serwisant-cp')
BuildFunctions.mkdir('public/assets-serwisant-cp/fonts')
BuildFunctions.mkdir('public/webfonts')

BuildFunctions.compressCSS(cssFiles, 'public/assets-serwisant-cp/serwisant-cp-vendor.css');
BuildFunctions.compressCSSdir('node_modules/serwisant-cp/css', 'public/assets-serwisant-cp/serwisant-cp.css')

BuildFunctions.compressJS(jsFiles, 'public/assets-serwisant-cp/serwisant-cp-vendor.js')
BuildFunctions.compressJS(jsAppFiles, 'public/assets-serwisant-cp/serwisant-cp.js')
BuildFunctions.withFiles('node_modules/serwisant-cp/js', function (file) {
  BuildFunctions.copy(file, 'public/assets-serwisant-cp')
})

BuildFunctions.withFiles('node_modules/bootstrap-icons/font/fonts', function (file) {
  BuildFunctions.copy(file, 'public/assets-serwisant-cp/fonts')
})
BuildFunctions.withFiles('node_modules/@fortawesome/fontawesome-free/webfonts', function (file) {
  BuildFunctions.copy(file, 'public/webfonts')
})

BuildFunctions.withFiles('node_modules/serwisant-cp/img', function (file) {
  BuildFunctions.copy(file, 'public/assets-serwisant-cp')
})
imgFiles.forEach(file => {
  BuildFunctions.copy(file, 'public/assets-serwisant-cp')
});
