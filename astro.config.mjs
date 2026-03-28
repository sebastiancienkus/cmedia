// @ts-check
import { defineConfig } from 'astro/config';

// https://astro.build/config
export default defineConfig({
    // Jeśli Twoja strona jest w domenie głównej (np. ai.sebastiancienkus.com), zostaw '/'
    // Jeśli byłaby w podfolderze, wpisz np. '/folder/'
    base: '/',

    // Wymuszamy generowanie statycznych plików HTML (najlepsze pod Seohost)
    output: 'static',

    // Opcjonalnie: usuwa końcowe ukośniki z adresów, co pomaga przy 404 na Apache
    build: {
        format: 'file'
    }
});