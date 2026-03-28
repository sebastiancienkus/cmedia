// @ts-check
import { defineConfig } from 'astro/config';

export default defineConfig({
    // Twój pełny adres URL - to pomaga Astro w generowaniu poprawnych ścieżek
    site: 'https://ai.sebastiancienkus.com',
    base: '/',
    output: 'static',
    build: {
        // Generuje 'index.html' zamiast folderów, co Seohost obsługuje najlepiej
        format: 'file'
    }
});
