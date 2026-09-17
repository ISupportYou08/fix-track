import { cpSync, mkdirSync, rmSync } from 'node:fs';

const outputDirectory = 'dist';
const publicFiles = [
    'apple-touch-icon.png',
    'favicon.ico',
    'favicon.svg',
    'fixtrack-logo.png',
    'robots.txt',
];

rmSync(outputDirectory, { recursive: true, force: true });
mkdirSync(outputDirectory, { recursive: true });
cpSync('public/build', `${outputDirectory}/build`, { recursive: true });

for (const publicFile of publicFiles) {
    cpSync(`public/${publicFile}`, `${outputDirectory}/${publicFile}`);
}
