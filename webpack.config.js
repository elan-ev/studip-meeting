const path = require('path'); // node.js uses CommonJS modules

const { VueLoaderPlugin }       = require('vue-loader');
const HtmlWebpackPlugin         = require('html-webpack-plugin');
const { CleanWebpackPlugin }    = require('clean-webpack-plugin');

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
			test: /.scss$/,
			use: [
				{
					loader: 'file-loader',
					options: {
						name: 'styles.css',
						outputPath: ''
					}
				},
                {
					loader: 'extract-loader',
				},
				{
					loader: 'css-loader?-url'
				},
				{
					loader: 'sass-loader'
				}
			]
		}]
    },
    plugins: [
        new CleanWebpackPlugin(),
        new VueLoaderPlugin(),
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
    ],
    resolve: {
        extensions: ['.vue', '.js'],
        alias: {
            '@': path.resolve(__dirname, 'vueapp')
        }
    }
};
