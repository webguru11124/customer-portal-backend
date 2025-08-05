module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  safelist: [
    'bg-red-50',
    'bg-green-50',
    'bg-purple-50',
    'text-red-800',
    'text-green-800',
    'text-purple-800'
  ],
  theme: {
    extend: {
      colors: {
        aptive: {
          300: '#e1ebe5',
          500: '#cddbd0',
          600: '#78856e',
          700: '#b8ccc9',
          800: '#49604d',
          900: '#344c38'
        }
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/line-clamp'),
  ],
}
