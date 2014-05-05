module.exports = function(grunt) {
  var pkg = grunt.file.readJSON('package.json');

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    
    concat: {
      options: {
        // define a string to put between each file in the concatenated output
        separator: ';'
      },
      dist: {
        // the files to concatenate
        src: [
          'app/lib/jquery/jquery.js',
          'app/lib/jquery/jquery-ui.js',
          'app/lib/nunjucks/nunjucks.js',
          'build/templates.js',
          'build/app.js'
        ],
        // the location of the resulting JS file
        dest: 'www/js/app.js'
      }
    },

    nunjucks: {
      precompile: {
        baseDir: 'templates/js/',
        src: 'templates/js/*',
        dest: 'build/templates.js',
        options: {
          // env: require('./app/lib/nunjucks/nunjucks'),
          name: function(filename) {
              return filename;
          }
        }
      }
    },

    browserify: {
      application: {
        files: {
          'build/app.js': [
            'app/components/**/*.js',
            'app/app.js'
          ]
        }
      } 
    },

    watch: {
      js: {
        options: {
            nospawn: true,
            livereload: true
          },
          files: [
            "app/**/*.js",
            "templates/js/**/*.j2"
          ],
          tasks: [
            "js"
          ]
      }
    },

    clean: {
      js: "build"
    }

  });

  /**
   * The cool way to load your grunt tasks
   * --------------------------------------------------------------------
   */
  Object.keys( pkg.dependencies ).forEach( function( dep ){
      if( dep.substring( 0, 6 ) === 'grunt-' ) grunt.loadNpmTasks( dep );
  });


  grunt.registerTask("default", [
    "watch"
  ]);

  grunt.registerTask("js", [
    "browserify",
    "nunjucks",
    "concat",
    "clean:js"
  ]);

};