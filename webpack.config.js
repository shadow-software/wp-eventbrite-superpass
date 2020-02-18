const { VueLoaderPlugin } = require('vue-loader')
var path = require('path');

module.exports = {
    mode: 'production',
    entry: './src/main.js',
    module: {
        rules: [
            {
                test: /\.vue$/,
                use: 'vue-loader'
            },
            {
                test: /\.scss$/,
                use: [
                    'vue-style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            },
            {
                test: /\.js$/,
                use: [ 'source-map-loader' ],
                enforce: 'pre'
            }
        ]
    },
    output : {
        path : path.join(__dirname, './assets/js/'),
        filename : 'bundle.js'
    },
    plugins: [
        new VueLoaderPlugin()
    ],
    devtool: 'source-map'
}