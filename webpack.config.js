const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  entry: {
    'editor': path.resolve(process.cwd(), 'assets/src/js', 'editor.js'),
    'preview-banner': path.resolve(process.cwd(), 'assets/src/js', 'preview-banner.js'),
    'preview': path.resolve(process.cwd(), 'assets/src/js', 'preview.js'),
    'settings': path.resolve(process.cwd(), 'assets/src/js', 'settings.js'),
  },
  output: {
    filename: '[name].js',
    path: path.resolve(process.cwd(), 'assets/dist'), // Output to dist root
  },
};