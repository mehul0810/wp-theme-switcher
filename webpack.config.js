const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  entry: {
    'ets-editor': path.resolve(process.cwd(), 'assets/src/js', 'ets-editor.js'),
    'ets-preview-banner': path.resolve(process.cwd(), 'assets/src/js', 'ets-preview-banner.js'),
    'ets-preview': path.resolve(process.cwd(), 'assets/src/js', 'ets-preview.js'),
    'ets-settings': path.resolve(process.cwd(), 'assets/src/js', 'ets-settings.js'),
  },
  output: {
    filename: '[name].js',
    path: path.resolve(process.cwd(), 'assets/dist'), // Output to dist root
  },
};