/* Build: npm install && npm run build:css   (outputs assets/css/tailwind.css)
   Palette must match assets/css/tokens.css. */
module.exports = {
    content: ['./*.php', './admin/**/*.php', './client/**/*.php', './includes/**/*.php', './assets/js/**/*.js'],
    theme: { extend: {
        fontFamily: { heading: ['Space Grotesk', 'sans-serif'], body: ['Inter', 'sans-serif'] },
        colors: {
            brand: { 50:'#e5f5f0',100:'#d2eee6',200:'#a8dfd0',300:'#79cdb7',400:'#43b39a',500:'#097a6d',600:'#087365',700:'#075e54' },
            dark: { 200:'#536b63',300:'#344b44',700:'#e5f5f0',800:'#d2eee6',900:'#ffffff',950:'#f4f8f6' }
        }
    }},
    plugins: []
};
