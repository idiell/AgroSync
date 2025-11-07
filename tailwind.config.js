/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // PHP/HTML views
    "./modules/**/*.{php,html}",
    "./components/**/*.{php,html}",
    "./pages/**/*.{php,html}",
    "./auth/**/*.{php,html}",
    "./views/**/*.{php,html}",
    "./index.php",

    // Any JS you actually author (NOT in node_modules or public)
    "./modules/**/*.js",
    "./components/**/*.js",
    "./pages/**/*.js",
    "./auth/**/*.js",
    "./views/**/*.js",
  ],
  theme: { extend: {} },
  plugins: [require("daisyui")],
};
