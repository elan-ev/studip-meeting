const path = require("path");

const { rspack } = require('@rspack/core');
const { VueLoaderPlugin } = require('rspack-vue-loader');
const { CssExtractRspackPlugin } = require("@rspack/core");
const HtmlRspackPlugin = require("html-rspack-plugin");

module.exports = {
    entry: ["./vueapp/app.js", "./assets/css/meetings.scss"], // the entry point
    output: {
        filename: "[name].[contenthash].js", // the output filename
        path: path.resolve(__dirname, "static"), // fully qualified path
        publicPath: "/",
        clean: true,
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: "rspack-vue-loader",
                options: {
                    experimentalInlineMatchResource: true,
                    compilerOptions: {
                        whitespace: 'preserve',
                        isCustomElement(tag) {
                            return ['altcha-widget'].includes(tag);
                        },
                    },
                },
            },
            {
                test: /\.scss$/,
                use: [
                    {
                        loader: CssExtractRspackPlugin.loader,
                    },
                    {
                        loader: "css-loader",
                        options: {
                            importLoaders: 2,
                        },
                    },
                    {
                        loader: "sass-loader",
                    },
                ],
            },
            {
                test: /\.css$/,
                use: ["vue-style-loader", "css-loader"],
            },
            {
                test: /\.(svg|png|jpe?g|gif|webp|ico|eot|ttf|woff2?)$/i,
                type: "asset/resource",
            },
        ],
    },
    plugins: [
        new VueLoaderPlugin(),
        new rspack.DefinePlugin({
            __VUE_OPTIONS_API__: JSON.stringify(true),
            __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
        }),
        new CssExtractRspackPlugin({
            filename: "styles.css",
        }),
        new HtmlRspackPlugin({
            template: "vueapp/course_index.php",
            inject: false,
            minify: false,
            filename: "../app/views/index/index.php",
        }),
        new HtmlRspackPlugin({
            template: "vueapp/admin_index.php",
            inject: false,
            minify: false,
            filename: "../app/views/admin/index.php",
        }),
        new HtmlRspackPlugin({
            template: "vueapp/lobby_index.php",
            inject: false,
            minify: false,
            filename: "../app/views/room/lobby.php",
        }),
        new HtmlRspackPlugin({
            template: "vueapp/lobby_index.php",
            inject: false,
            minify: false,
            filename: "../app/views/room/qrcode_lobby.php",
        }),
        new HtmlRspackPlugin({
            template: "vueapp/lobby_index.php",
            inject: false,
            minify: false,
            filename: "../app/views/room/public_lobby.php",
        }),
    ],
    resolve: {
        extensions: [".vue", ".js"],
        fallback: {
            fs: false,
            url: false,
        },
        alias: {
            "@": path.resolve(__dirname, "vueapp"),
            "@studip": path.resolve(__dirname, "vueapp/components/studip"),
            "@meeting": path.resolve(__dirname, "vueapp/components/meeting"),
            "@vue-pdf-viewer/viewer$": path.resolve(__dirname, "node_modules/@vue-pdf-viewer/viewer/dist/index.cjs"),
        },
    },
    node: {
        __filename: false,
    },
    externals: {
        vue: 'Vue',
        vuex: 'Vuex',
    },
    externalsType: 'global',
};
