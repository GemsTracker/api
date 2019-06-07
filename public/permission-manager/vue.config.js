module.exports = {
    css: {
        loaderOptions: {
            sass: {
                data: `@import "@/assets/styles/main.scss";`,
                outputDir: '@/dist/css/main.css'
            }
        }
    },
};