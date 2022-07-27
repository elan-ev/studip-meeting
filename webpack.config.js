const path = require('path'); // node.js uses CommonJS modules

const { VueLoaderPlugin }       = require('vue-loader');
const HtmlWebpackPlugin         = require('html-webpack-plugin');
const { CleanWebpackPlugin }    = require('clean-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");


module.exports = {
    entry: ['./vueapp/app.js', './assets/css/meetings.scss'], // the entry point
    output: {
        filename: '[name].[contenthash].js', // the output filename
        path: path.resolve(__dirname, 'static'), // fully qualified path
        publicPath: '/'
    },
    module: {
        rules: [{
            test: /\.vue$/,
            use: 'vue-loader'
        }, {
			test: /\.scss$/,
            use: [
                {
                    loader: MiniCssExtractPlugin.loader
                },
                {
                    loader: "css-loader",
                    options: {
                        importLoaders: 2
                    }
                },
                {
                    loader: 'sass-loader'
                }
            ]
		}, {
            test: /\.css$/,
            use: [
              'vue-style-loader',
              'css-loader'
            ]
          }]
    },
    plugins: [
        new CleanWebpackPlugin(),
        new VueLoaderPlugin(),
        new MiniCssExtractPlugin({
            filename: 'styles.css',
        }),
        new CssMinimizerPlugin({
            minimizerOptions: {
                discardComments: {
                    removeAll: true
                },
                safe: true
            }
        }),
        new HtmlWebpackPlugin({
            template: 'vueapp/course_index.php',
            inject: false,
            minify: false,
            filename: '../app/views/index/index.php'
        }),
        new HtmlWebpackPlugin({
            template: 'vueapp/admin_index.php',
            inject: false,
            minify: false,
            filename: '../app/views/admin/index.php'
        }),
        new HtmlWebpackPlugin({
            template: 'vueapp/lobby_index.php',
            inject: false,
            minify: false,
            filename: '../app/views/room/lobby.php'
        }),
        new HtmlWebpackPlugin({
            template: 'vueapp/lobby_index.php',
            inject: false,
            minify: false,
            filename: '../app/views/room/qrcode_lobby.php'
        }),
        new HtmlWebpackPlugin({
            template: 'vueapp/lobby_index.php',
            inject: false,
            minify: false,
            filename: '../app/views/room/public_lobby.php'
        }),
    ],
    resolve: {
        extensions: ['.vue', '.js'],
        alias: {
            '@': path.resolve(__dirname, 'vueapp')
        }
    }
};
