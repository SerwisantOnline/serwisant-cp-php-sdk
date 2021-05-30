const fs = require('fs');
const path = require('path');

function mkdir() {
  let app_dir = process.cwd();
  fs.mkdir(path.join(app_dir, 'public/assets'), (err) => {
    if (err && err.code !== 'EEXIST') {
      throw err;
    }
    console.log('mkdir public/assets');
  });
}

function copy(file, prefix = '') {
  let app_dir = process.cwd();
  let file_parts = file.split('/');
  let file_name = file_parts[file_parts.length - 1]
  let target = path.join(app_dir, 'public/assets', prefix + file_name);
  try {
    fs.unlinkSync(target)
  } catch (err) {
    if (err.code !== 'ENOENT') {
      throw err;
    }
  }
  fs.symlink(path.join(app_dir, file), target, (err) => {
    if (err) throw err;
    console.log('cp ' + prefix + file_name);
  });
}

function with_files(in_dir, perform) {
  let app_dir = process.cwd();
  fs.readdir(path.join(app_dir, in_dir), (err, files) => {
    files.forEach(file => {
      perform(in_dir + '/' + file);
    });
  });
}

mkdir('public/assets')

copy('node_modules/lodash/lodash.min.js')

copy('node_modules/jquery/dist/jquery.min.js')

copy('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js')
copy('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map')
copy('node_modules/bootstrap/dist/css/bootstrap.min.css')
copy('node_modules/bootstrap/dist/css/bootstrap.min.css.map')

copy('node_modules/bootstrap-cookie-alert/cookiealert.js')

copy('node_modules/datetimepicker/dist/DateTimePicker.min.js')
copy('node_modules/datetimepicker/dist/DateTimePicker.min.css')

copy('node_modules/filepond/dist/filepond.min.js')
copy('node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js')
copy('node_modules/filepond/dist/filepond.min.css')
copy('node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css')

with_files('vendor/serwisant/serwisant-cp/assets', function (file) {
  copy(file)
})