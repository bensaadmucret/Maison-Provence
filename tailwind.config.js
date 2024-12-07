import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.twig",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2B4C7E', // Bleu provence
          dark: '#1A3A6C',
          light: '#3C5D8F'
        },
        secondary: {
          DEFAULT: '#D4B12F', // Jaune provence
          dark: '#C3A01E',
          light: '#E5C240'
        },
        provence: {
          lavender: '#967BB6',    // Lavande
          olive: '#8B8B4B',       // Olive
          ochre: '#D27D2D',       // Ocre
          terracotta: '#C94C4C',  // Terre cuite
          sage: '#9CAF88',        // Sauge
          sky: '#7CB7D4',         // Ciel provençal
          stone: '#D6CFC7'        // Pierre de Provence
        },
        amber: {
          50: '#fff7ed',
          100: '#ffedd5',
          200: '#fed7aa',
          300: '#fdba74',
          400: '#fb923c',
          500: '#f97316',
          600: '#ea580c',
          700: '#c2410c',
          800: '#9a3412',
          900: '#7c2d12',
          950: '#431407'
        },
        teal: {
          50: '#f0fdfa',
          100: '#ccfbf1',
          200: '#99f6e4',
          300: '#5eead4',
          400: '#2dd4bf',
          500: '#14b8a6',
          600: '#0d9488',
          700: '#0f766e',
          800: '#115e59',
          900: '#134e4a',
          950: '#042f2e',
        }
      }
    },
  },
  plugins: [
    typography,
  ],
}
