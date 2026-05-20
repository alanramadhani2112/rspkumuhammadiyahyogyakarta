export default {
  content: [
    './app/**/*.php',
    './resources/views/**/*.twig',
    './resources/js/**/*.js',
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
      // Standardized spacing scale
      spacing: {
        '4.5': '1.125rem',  // 18px
        '13': '3.25rem',    // 52px
        '15': '3.75rem',    // 60px
        '18': '4.5rem',     // 72px
        '22': '5.5rem',     // 88px
      },
      // Standardized typography scale (Major Third - 1.25)
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1.5' }],      // 12px
        'sm': ['0.875rem', { lineHeight: '1.5' }],     // 14px
        'base': ['1rem', { lineHeight: '1.6' }],       // 16px
        'lg': ['1.125rem', { lineHeight: '1.6' }],     // 18px
        'xl': ['1.25rem', { lineHeight: '1.4' }],      // 20px
        '2xl': ['1.563rem', { lineHeight: '1.3' }],    // 25px
        '3xl': ['1.953rem', { lineHeight: '1.2' }],    // 31px
        '4xl': ['2.441rem', { lineHeight: '1.2' }],    // 39px
        '5xl': ['3.052rem', { lineHeight: '1.1' }],    // 49px
        '6xl': ['3.815rem', { lineHeight: '1.1' }],    // 61px
      },
      // Medical-grade border radius (less rounded, more clinical)
      borderRadius: {
        'none': '0',
        'sm': '0.25rem',    // 4px - chips, badges, small inputs
        'DEFAULT': '0.375rem', // 6px - buttons, small cards
        'md': '0.5rem',     // 8px - cards, inputs
        'lg': '0.75rem',    // 12px - large cards, panels
        'xl': '1rem',       // 16px - hero sections, feature panels
        '2xl': '1.5rem',    // 24px - extra large sections
      },
      boxShadow: {
        soft: '0 22px 44px rgba(15, 23, 42, 0.08)',
        'sm': '0 2px 8px rgba(15, 23, 42, 0.08)',
        'md': '0 4px 16px rgba(15, 23, 42, 0.08)',
        'lg': '0 8px 24px rgba(15, 23, 42, 0.10)',
      },
      // Standardized line heights
      lineHeight: {
        'tight': '1.2',
        'snug': '1.4',
        'normal': '1.6',
        'relaxed': '1.8',
      },
    },
  },
  plugins: [],
};
