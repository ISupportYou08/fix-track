import { cpSync, mkdirSync, rmSync } from 'node:fs';

const outputDirectory = 'dist';
const publicFiles = [
    'admin-workspace-background.png',
    'apple-touch-icon.png',
    'customer-registration-abstract.jpg',
    'customer-registration-bg.jpg',
    'favicon.ico',
    'favicon.svg',
    'fixtrack-logo.png',
    'robots.txt',
    'sign-in-repair.jpg',
];

rmSync(outputDirectory, { recursive: true, force: true });
mkdirSync(outputDirectory, { recursive: true });
cpSync('public/build', `${outputDirectory}/build`, { recursive: true });

for (const publicFile of publicFiles) {
    cpSync(`public/${publicFile}`, `${outputDirectory}/${publicFile}`);
}
