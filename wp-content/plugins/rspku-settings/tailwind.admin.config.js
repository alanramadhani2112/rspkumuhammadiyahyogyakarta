/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './includes/**/*.php',
    './assets/**/*.js',
    './assets/admin.source.css',
  ],
  theme: {
    extend: {
      colors: {
        hospital: {
          50: '#eef9f2',
          100: '#d7f1e0',
          200: '#b2e2c6',
          300: '#7dcca0',
          400: '#47b676',
          500: '#179b56',
          600: '#0c8f45',
          700: '#086d35',
          800: '#07552c',
          900: '#063f22',
        },
      },
      boxShadow: {
        soft: '0 22px 44px rgba(15, 23, 42, 0.08)',
      },
    },
  },
  corePlugins: {
    container: false,
    preflight: false,
  },
  plugins: [],
};
