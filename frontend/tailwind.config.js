/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        arena: {
          bg: '#0B0F17',
          card: '#111827',
          panel: '#0f1724',
          line: '#1f2937',
        },
      },
      boxShadow: {
        glow: '0 20px 70px rgba(34, 211, 238, 0.14)',
        greenGlow: '0 18px 60px rgba(74, 222, 128, 0.16)',
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'monospace'],
      },
    },
  },
  plugins: [],
};
