/**
 * @type {import("webpack").Configuration}
 */
module.exports = {
    entry: __dirname + "/src/js/index.ts",
    mode: "development",
    devtool: "inline-source-map",
    output: {
        path: __dirname + "/dist/",
        filename: "index.js"
    },
    resolve: {
        extensions: [".ts", ".js", ".css", ".scss", ".sass"]
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: "ts-loader"
            },
            {
                test: /\.s[ac]ss$/,
                use: [
                    {
                        loader: "style-loader"
                    },
                    {
                        loader: "css-loader"
                    },
                    {
                        loader: "sass-loader",
                        options: {
                            implementation: require("sass")
                        }
                    }
                ]
            }
        ]
    }
};