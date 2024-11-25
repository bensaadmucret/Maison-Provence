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
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}
