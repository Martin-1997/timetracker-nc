const { VueLoaderPlugin } = require('vue-loader')
const webpack = require('webpack')
const path = require('path')

module.exports = {
  devtool: 'cheap-module-source-map',
  entry: {
    main: path.join(__dirname, 'src', 'main.js'),
  },
  output: {
    filename: '[name].js',
    path: path.join(__dirname, 'dist'),
  },
  resolve: {
    extensions: ['.js', '.vue'],
    alias: {
      // Replace Vue 2-only @linusborg/vue-simple-portal with a Vue 3 compatible shim.
      // @nextcloud/vue 8.x depends on it but the 0.1.5 version calls Vue.extend()
      // at module load time, which crashes with Vue 3 (no default export).
      '@linusborg/vue-simple-portal': path.resolve(__dirname, 'shims/vue-simple-portal.js'),
    },
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader'],
      },
      {
        test: /\.(png|jpg|jpeg|gif|svg|woff|woff2|eot|ttf|otf)$/,
        type: 'asset/resource',
      },
    ],
  },
  plugins: [
    new VueLoaderPlugin(),
    new webpack.DefinePlugin({
      appName: JSON.stringify('timetracker'),
      appVersion: JSON.stringify('0.0.86'),
    }),
  ],
}
