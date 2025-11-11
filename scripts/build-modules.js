#!/usr/bin/env node

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Read module statuses
const modulesStatusPath = path.join(__dirname, '..', 'modules_statuses.json');
const modulesStatus = JSON.parse(fs.readFileSync(modulesStatusPath, 'utf-8'));

const modulesPath = path.join(__dirname, '..', 'Modules');
const modules = fs.readdirSync(modulesPath);

console.log('Building module assets...\n');

let buildCount = 0;
let errorCount = 0;

for (const module of modules) {
    // Skip if module is disabled
    if (modulesStatus[module] !== true) {
        console.log(`⏭️  Skipping ${module} (disabled)`);
        continue;
    }

    const modulePath = path.join(modulesPath, module);
    const packageJsonPath = path.join(modulePath, 'package.json');
    const viteConfigPath = path.join(modulePath, 'vite.config.js');

    // Check if module has package.json and vite.config.js
    if (!fs.existsSync(packageJsonPath) || !fs.existsSync(viteConfigPath)) {
        console.log(`⏭️  Skipping ${module} (no assets to build)`);
        continue;
    }

    try {
        console.log(`📦 Building ${module}...`);

        // Change to module directory
        process.chdir(modulePath);

        // Install dependencies if package-lock.json exists, otherwise just install
        if (fs.existsSync(path.join(modulePath, 'package-lock.json'))) {
            execSync('npm ci', { stdio: 'inherit' });
        } else {
            execSync('npm install', { stdio: 'inherit' });
        }

        // Build assets
        execSync('npm run build', { stdio: 'inherit' });

        console.log(`✅ ${module} built successfully\n`);
        buildCount++;
    } catch (error) {
        console.error(`❌ Error building ${module}:`, error.message);
        errorCount++;
    } finally {
        // Return to root directory
        process.chdir(path.join(__dirname, '..'));
    }
}

console.log(`\n📊 Build Summary:`);
console.log(`   ✅ Successfully built: ${buildCount} modules`);
if (errorCount > 0) {
    console.log(`   ❌ Failed: ${errorCount} modules`);
    process.exit(1);
}

