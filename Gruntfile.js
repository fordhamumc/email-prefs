'use strict';

module.exports = function (grunt) {
    // Show elapsed time after tasks run
    require('time-grunt')(grunt);
    // Load all Grunt tasks
    require('load-grunt-tasks')(grunt);

    grunt.initConfig({
        paths: {
            dev: 'app',
            dist: 'dist',
            tmp: '.tmp'
        },

        php: {
            dev: {
                options: {
                    hostname: '127.0.0.1',
                    port: 8010,
                    base: '<%= paths.tmp %>', // Project root
                    keepalive: false,
                    open: false
                }
            }
        },
        browserSync: {
            dev: {
                bsFiles: {
                    src: [
                        '<%= paths.tmp %>/**/*.{html,php,js,css,jpg,png,gif,svg}'
                    ]
                },
                options: {
                    proxy: '<%= php.dev.options.hostname %>:<%= php.dev.options.port %>',
                    port: 8080,
                    open: true,
                    watchTask: true
                }
            }
        },
        watch: {
            files: {
                files: ['<%= paths.dev %>/**/*.{ini,json,php,html,png,jpg,jpeg,gif,svg}'],
                tasks: ['copy:dev']
            },
            sass: {
                files: ['<%= paths.dev %>/**/*.{sass,scss}'],
                tasks: ['sass:dev','autoprefixer:dev']
            },
            js: {
                files: ['<%= paths.dev %>/**/*.js'],
                tasks: ['babel:dev']
            }
        },

        clean: {
            dev: ['<%= paths.tmp %>/*'],
            dist: ['<%= paths.tmp %>/*','<%= paths.dist %>/*']
        },
        copy: {
            dev: {
                files: [{
                    expand: true,
                    dot: true,
                    cwd: '<%= paths.dev %>',
                    src: [
                        'img/**/*.{png,jpg,jpeg,gif,svg}',
                        '**/*.{html,php,ini,json}'
                    ],
                    dest: '<%= paths.tmp %>'
                }]
            },
            dist: {
                files: [{
                    expand: true,
                    dot: true,
                    cwd: '<%= paths.dev %>',
                    src: [
                        'img/**/*.{png,jpg,jpeg,gif,svg}',
                        '**/*.{html,php,ini,json}'
                    ],
                    dest: '<%= paths.dist %>'
                }]
            }
        },

        imagemin: {
            dist: {
                options: {
                    progressive: true
                },
                files: [{
                    expand: true,
                    cwd: '<%= paths.dist %>',
                    src: '**/*.{jpg,jpeg,png}',
                    dest: '<%= paths.dist %>'
                }]
            }
        },

        sass: {
            dev: {
                options: {
                    debugInfo: true,
                    lineNumbers: true,
                    style: 'expanded'
                },
                files: [{
                    expand: true,
                    cwd: '<%= paths.dev %>',
                    src: 'css/**/*.{scss,sass}',
                    dest: '<%= paths.tmp %>',
                    ext: '.css'
                }]
            },
            dist: {
                options: {
                    debugInfo: false,
                    lineNumbers: false,
                    style: 'compressed'
                },
                files: [{
                    expand: true,
                    cwd: '<%= paths.dev %>',
                    src: 'css/**/*.{scss,sass}',
                    dest: '<%= paths.dist %>',
                    ext: '.css'
                }]
            }
        },
        autoprefixer: {
            options: {
                browsers: ['last 2 versions', '> 5%']
            },
            dev: {
                expand: true,
                cwd: '<%= paths.tmp %>',
                src: 'css/*.css',
                dest: '<%= paths.tmp %>'
            },
            dist: {
                expand: true,
                cwd: '<%= paths.dist %>',
                src: 'css/*.css',
                dest: '<%= paths.dist %>'
            }
        },

        babel: {
            options: {
                sourceMap: true,
                presets: ['es2015']
            },
            dev: {
                files: [
                    {
                        expand: true,
                        cwd: '<%= paths.dev %>',
                        src: ['**/*.js'],
                        dest: '<%= paths.tmp %>'
                    }
                ]
            }
        },
        uglify: {
            dist: {
                options: {
                    sourceMap: true,
                    sourceMapIncludeSources: true,
                    sourceMapIn: '<%= paths.tmp %>/js/main.js.map'
                },
                files: {
                    '<%= paths.dist %>/js/main.js': ['<%= paths.tmp %>/js/main.js']
                }
            }
        },
        filerev: {
            options: {
                length: 4
            },
            dist: {
                files: [{
                    src: [
                        '<%= paths.dist %>/js/**/*.js',
                        '<%= paths.dist %>/css/**/*.css',
                        '<%= paths.dist %>/img/**/*.{gif,jpg,jpeg,png,svg,webp}'
                    ]
                }]
            }
        },
        usemin: {
            html: ['<%= paths.dist %>/index.php', '<%= paths.dist %>/inc/**/*.php'],
            options: {
                assetsDirs: ['<%= paths.dist %>']
            }
        }

    });

    grunt.registerTask('serve', [
        'clean:dev',
        'copy:dev',
        'sass:dev',
        'autoprefixer:dev',
        'babel',
        'php',
        'browserSync',
        'watch'
    ]);

    grunt.registerTask('build', [
        'clean:dist',
        'copy:dist',
        'imagemin',
        'sass:dist',
        'autoprefixer:dist',
        'babel',
        'uglify',
        'filerev',
        'usemin'
    ]);


    grunt.registerTask('default', ['serve']);
};
