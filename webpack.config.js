
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
  ...defaultConfig,
  entry: {
    'individual': path.resolve(process.cwd(), 'assets/src/js', 'individual.js'),
    'preview-banner': path.resolve(process.cwd(), 'assets/src/js', 'preview-banner.js'),
    'preview': path.resolve(process.cwd(), 'assets/src/js', 'preview.js'),
    'settings': path.resolve(process.cwd(), 'assets/src/js', 'settings.js'),
  },
  output: {
    filename: '[name].js',
    path: path.resolve(process.cwd(), 'assets/dist'),
    assetModuleFilename: 'images/[name][ext][query]', // Output images to assets/dist/images
  },
  module: {
    rules: [
      ...(defaultConfig.module && defaultConfig.module.rules ? defaultConfig.module.rules : []),
      {
        test: /\.(png|jpe?g|gif|svg)$/i,
        include: path.resolve(process.cwd(), 'assets/src/images'),
        type: 'asset/resource',
        generator: {
          filename: 'images/[name][ext][query]',
        },
        use: [
          {
            loader: 'image-webpack-loader',
            options: {
              mozjpeg: { progressive: true },
              optipng: { enabled: true },
              pngquant: { quality: [0.65, 0.90], speed: 4 },
              gifsicle: { interlaced: false },
              webp: { quality: 75 }
            }
          }
        ]
      }
    ]
  },
  plugins: [
    ...(defaultConfig.plugins || []),
    new CopyWebpackPlugin({
      patterns: [
        {
          from: path.resolve(process.cwd(), 'assets/src/images'),
          to: path.resolve(process.cwd(), 'assets/dist/images'),
          globOptions: {
            ignore: ['**/.DS_Store'],
          },
          noErrorOnMissing: true,
        },
      ],
    }),
  ],
};