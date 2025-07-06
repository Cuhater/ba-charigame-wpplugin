/** @type {import('tailwindcss').Config} */
module.exports = {
	content: [
		"./templates/*.php",
		"./src/games/*/*.php",
		"./src/games/*/*.js",
	],
	theme: {
		extend: {
			colors: {
				primary: 'var(--primary-color)',
				secondary: 'var(--secondary-color)',
				teritary: "var(--teritary-color)",
				input: "#f0f0f0",
			},
			fontFamily: {
				main: "Main",
			},
		},
	},
	plugins: [require("tailwindcss")],
}

