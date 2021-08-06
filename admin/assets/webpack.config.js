/* eslint-disable strict,no-unused-vars,new-cap */
const path = require('path');
const fs = require('fs');
const webpack = require('webpack');
const Fiber = require('fibers');
const {
  VueLoaderPlugin
} = require('vue-loader');

const plugins = {
  progress: require('webpackbar'),
  clean: require('clean-webpack-plugin'),
  extractCSS: require('mini-css-extract-plugin'),
  sync: require('browser-sync-webpack-plugin'),
  html: require('html-webpack-plugin'),
  copy: require('copy-webpack-plugin'),
  sri: require('webpack-subresource-integrity'),
  vue: require('vue-loader'),
};

const pages = fs
    .readdirSync(path.resolve(__dirname, 'src/pages'))
    .filter(fileName => (fileName.endsWith('.html') || fileName.endsWith('.pug')));

module.exports = (env = {}, argv) => {
  const isProduction = argv.mode === 'production';

  const config = {
    context: path.resolve(__dirname, 'src'),

    entry: {
      app: [
        './styles/app.scss',
        './scripts/app.js',
      ],
    },

    output: {
      path: path.resolve(__dirname, 'dist'),
      publicPath: '',
      filename: 'scripts/[name].js',
      crossOriginLoading: 'anonymous',
    },

    module: {
      rules: [{
        test: /\.((s[ac]|c)ss)$/,
        use: [{
          loader: plugins.extractCSS.loader,
          options: {
            publicPath: '../', // use relative path for everything in CSS
          },
        },
          {
            loader: 'css-loader',
            options: {
              sourceMap: !isProduction,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              ident: 'postcss',
              sourceMap: !isProduction,
              plugins: (() => [
                require('autoprefixer')(),
                ...isProduction ? [
                  require('cssnano')({
                    preset: ['default', {
                      minifySelectors: false,
                    }],
                  }),
                ] : [],
              ]),
            },
          },
          {
            loader: 'sass-loader',
            options: {
              implementation: require('sass'),
              fiber: Fiber,
              outputStyle: 'expanded',
              sourceMap: !isProduction,
            },
          },
        ],
      },
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                '@babel/preset-env',
              ],
            },
          },
        },
        {
          test: /\.pug$/,
          use: {
            loader: 'pug-loader',
            options: {
              pretty: true,
            },
          },

        },
        {
          test: /\.vue$/,
          use: 'vue-loader',
        },
        {
          test: /\.(gif|png|jpe?g|svg)$/i,
          exclude: /fonts/,
          use: [{
            loader: 'file-loader',
            options: {
              name: '[path][name].[ext]',
              // publicPath: '..' // use relative path
            },
          },],
        },
        {
          test: /.(ttf|otf|eot|svg|woff(2)?)(\?[a-z0-9]+)?$/,
          exclude: /images/,
          use: [{
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: 'fonts/',
              // publicPath: '../fonts/' // use relative path
            },
          }],
        },
        {
          test: /\.html$/,
          use: {
            loader: 'html-loader',
            options: {
              minimize: false,
              removeComments: false,
              collapseWhitespace: false,
              removeScriptTypeAttributes: true,
              removeStyleTypeAttributes: true,
            },
          },
        },
      ],
    },

    devServer: {
      contentBase: path.join(__dirname, 'src'),
      port: 8080,
      overlay: {
        warnings: true,
        errors: true,
      },
      quiet: true,
    },

    plugins: (() => {
      const common = [
        new plugins.extractCSS({
          filename: 'styles/[name].css',
        }),
        new plugins.progress({
          color: '#7EBC2D',
        }),
        new webpack.ProvidePlugin({
          $: 'jquery/dist/jquery.min.js',
          jQuery: 'jquery/dist/jquery.min.js',
          'window.jQuery': 'jquery/dist/jquery.min.js',
        }),
        new VueLoaderPlugin(),
        ...pages.map(page => new plugins.html({
          template: `${path.resolve(__dirname, 'src/pages')}/${page}`,
          filename: `${page.split('.')[0]}.html`,
        })),
      ];

      const production = [
        new plugins.clean(['dist']),
        
        new plugins.copy([{
          from: 'images',
          to: 'images',
        }]),
      ];

      const development = [
        new plugins.sync({
          host: 'localhost',
          port: 3000,
          proxy: 'http://localhost:8080/',
        }, {
          reload: false,
        },),
      ];

      return isProduction ?
          common.concat(production) :
          common.concat(development);
    })(),

    devtool: (() => (isProduction ?
        '' // 'hidden-source-map'
        :
        'source-map'))(),

    resolve: {
      modules: [path.resolve(__dirname, 'src'), 'node_modules'],
      alias: {
        '~': path.resolve(__dirname, 'src/scripts/'),
      },
    },

    stats: 'errors-only',
  };

  return config;
};