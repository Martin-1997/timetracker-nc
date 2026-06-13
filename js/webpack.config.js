const { VueLoaderPlugin } = require('vue-loader')
const webpack = require('webpack')
const path = require('path')
const fs = require('fs')

const infoXml = fs.readFileSync(path.join(__dirname, '..', 'appinfo', 'info.xml'), 'utf8')
const appVersion = (infoXml.match(/<version>([^<]+)<\/version>/) || [])[1] || '0.0.0'

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
      appVersion: JSON.stringify(appVersion),
    }),
  ],
}
