/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./public/js/**/*.js",
  ],
  theme: {
    extend: {
      // Moved from the old in-browser Tailwind CDN config in layouts/header.blade.php.
      colors: {
        'smart-blue': '#0466c8ff',
        'sapphire': '#0353a4ff',
        'regal-navy': '#023e7dff',
        'prussian-blue': '#003595',
        'prussian-blue-2': '#003595',
        'prussian-blue-3': '#003595',
        'twilight-indigo': '#003595',
        'blue-slate': '#5c677dff',
        'slate-grey': '#5c677dff',
        'lavender-grey': '#5c677dff',

        'munti-blue-0': '#003595',
        'munti-blue-1': '#5c677dff',
        'munti-blue-2': '#2196f3',
        'munti-yellow-0': '#ffb702ff',
        'munti-yellow-1': '#fccc3dff',
        'munti-yellow-2': '#ffd966',
        'munti-red-0': '#e2261b',
        'munti-green-0': '#d9f99d',
        'munti-green-1': '#84cc16',
        'munti-white-0': '#e0e0e0',
        'munti-black-0': '#080808',
      },
    },
  },
  plugins: [],
}
