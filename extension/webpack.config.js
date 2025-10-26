const path = require("path");

module.exports = {
    mode: "production",
    target: "web",
    entry: {
        background: "./background.js",
        "popup-bundle": ["./popup.js", "./clear-form.js"],
        "content/sync-auth": "./content/sync-auth.js",
        "content/form-detection": "./content/form-detection.js",
        "content/field-filling": "./content/field-filling.js",
        "content/form-filling": "./content/form-filling.js",
        "content/forms-observer": "./content/forms-observer.js",
        "content/floating-button": "./content/floating-button.js",
    },
    output: {
        path: path.resolve(__dirname, "dist"),
        filename: "[name].js",
        clean: true,
    },
    resolve: {
        extensions: [".js"],
    },
    optimization: {
        minimize: true,
    },
};
