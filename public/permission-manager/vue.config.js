module.exports = {
    filenameHashing: false,
    outputDir: process.env.NODE_ENV === 'production' ? 'dist' : 'dist/js',
    css: {
        loaderOptions: {
            sass: {
                data: `@import "@/assets/styles/main.scss";`,
                outputDir: '@/dist/css/main.css'
            }
        }
    },
};