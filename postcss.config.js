module.exports = {
  plugins: [
    require('postcss-preset-env')({
      stage: 0,
      autoprefixer: {
        grid: true,
        flexbox: true
      }
    })
  ]
};