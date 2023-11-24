const fs = require('fs');
const path = require('path');
const UglifyJS = require(process.cwd() + "/node_modules/uglify-js");
var uglifycss = require(process.cwd() + '/node_modules/uglifycss');

const BuildFunctions = {
  currentDir: function () {
    return process.cwd();
  },

  mkdir: function (dir) {
    let app_dir = BuildFunctions.currentDir();
    try {
      fs.mkdirSync(path.join(app_dir, dir));
      console.log('mkdir ' + dir);
    } catch (err) {
      if (err.code !== 'EEXIST') {
        throw err;
      } else {
        console.log('mkdir ' + dir);
      }
    }
  },

  copy: function (file, target_dir, action) {
    let app_dir = BuildFunctions.currentDir();

    let file_parts = file.split('/');
    let file_name = file_parts[file_parts.length - 1]

    let target = path.join(app_dir, target_dir, file_name);

    try {
      fs.unlinkSync(target)
    } catch (err) {
      if (err.code !== 'ENOENT') {
        throw err;
      }
    }
    if (action == 'symlink') {
      fs.symlink(path.join(app_dir, file), target, (err) => {
        if (err) throw err;
        console.log('ln ' + file_name);
      });
    } else {
      fs.copyFile(path.join(app_dir, file), target, (err) => {
        if (err) throw err;
        console.log('cp ' + file_name);
      });
    }
  },

  copyFiles: function (files) {
    files.forEach(file => {
      BuildFunctions.copy(file)
    })
  },

  symlink: function (file) {
    return BuildFunctions.copy(file, 'symlink')
  },

  symlinkFiles: function (files) {
    files.forEach(file => {
      BuildFunctions.symlink(file)
    })
  },

  withFiles: function (in_dir, perform) {
    let app_dir = BuildFunctions.currentDir();
    fs.readdir(path.join(app_dir, in_dir), (err, files) => {
      files.forEach(file => {
        perform(in_dir + '/' + file);
      });
    });
  },

  compressCSS: function (files, targetFile) {
    var uglified = uglifycss.processFiles(files);
    fs.writeFileSync(BuildFunctions.currentDir() + '/' + targetFile, uglified);
    console.log('compressed CSS files:', files);
  },

  compressCSSdir: function (dir, target) {
    var files = []
    fs.readdirSync(dir).forEach(file => {
      files.push(dir + '/' + file)
    })
    BuildFunctions.compressCSS(files, target);
  },

  compressJS: function (files, targetFile) {
    var code = {}
    files.forEach(filePath => {
      code[filePath] = fs.readFileSync(BuildFunctions.currentDir() + '/' + filePath, 'utf8')
    });
    var result = UglifyJS.minify(code, {
      mangle: false,
      compress: false,
      output: {
        comments: "some"
      }
    });
    fs.writeFileSync(BuildFunctions.currentDir() + '/' + targetFile, result.code);
    console.log('compressed JS files:', files);
  },

  compressJSdir: function (dir, target) {
    var files = []
    fs.readdirSync(dir).forEach(file => {
      files.push(dir + '/' + file)
    })
    BuildFunctions.compressJS(files, target);
  }
}

module.exports = BuildFunctions;