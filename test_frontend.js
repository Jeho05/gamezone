/**
 * AUDIT COMPLET DU FRONTEND REACT/NEXT.JS
 * Vérifie tous les fichiers frontend pour détecter les erreurs
 */

const fs = require('fs');
const path = require('path');

const results = {
    totalFiles: 0,
    jsxFiles: 0,
    jsFiles: 0,
    errors: [],
    warnings: [],
    passed: 0,
    failed: 0
};

const frontendPath = path.join(__dirname, 'createxyz-project', '_', 'apps', 'web', 'src');

// Patterns d'erreurs communes
const errorPatterns = [
    { pattern: /console\.log\(/g, type: 'warning', message: 'Console.log trouvé (à retirer en prod)' },
    { pattern: /debugger/g, type: 'error', message: 'Debugger trouvé (à retirer)' },
    { pattern: /var\s+\w+/g, type: 'warning', message: 'Utilisation de var (préférer let/const)' },
    { pattern: /==(?!=)/g, type: 'warning', message: 'Utilisation de == au lieu de ===' },
    { pattern: /\.then\(\s*\)/g, type: 'warning', message: 'Promise sans gestion d\'erreur' },
    { pattern: /fetch\(/g, type: 'info', message: 'Appel fetch direct (vérifier gestion erreurs)' }
];

// Vérifications spécifiques React
const reactPatterns = [
    { pattern: /useState\s*<[^>]*>\s*\(/g, type: 'info', message: 'useState typé correctement' },
    { pattern: /useEffect\(\s*\(\)\s*=>\s*\{[^}]*\}\s*,\s*\[\s*\]\s*\)/g, type: 'info', message: 'useEffect sans dépendances' },
    { pattern: /className="\{/g, type: 'error', message: 'Erreur de syntaxe className' }
];

function analyzeFile(filePath) {
    try {
        const content = fs.readFileSync(filePath, 'utf8');
        const relativePath = path.relative(frontendPath, filePath);
        
        results.totalFiles++;
        if (filePath.endsWith('.jsx') || filePath.endsWith('.tsx')) {
            results.jsxFiles++;
        } else if (filePath.endsWith('.js') || filePath.endsWith('.ts')) {
            results.jsFiles++;
        }

        let fileHasErrors = false;

        // Vérifier les patterns d'erreurs
        errorPatterns.forEach(({ pattern, type, message }) => {
            const matches = content.match(pattern);
            if (matches) {
                const issue = {
                    file: relativePath,
                    type,
                    message,
                    count: matches.length
                };
                
                if (type === 'error') {
                    results.errors.push(issue);
                    fileHasErrors = true;
                } else if (type === 'warning') {
                    results.warnings.push(issue);
                }
            }
        });

        // Vérifier les patterns React
        if (filePath.endsWith('.jsx') || filePath.endsWith('.tsx')) {
            reactPatterns.forEach(({ pattern, type, message }) => {
                const matches = content.match(pattern);
                if (matches && type === 'error') {
                    results.errors.push({
                        file: relativePath,
                        type,
                        message,
                        count: matches.length
                    });
                    fileHasErrors = true;
                }
            });
        }

        // Vérifier les imports manquants (basique)
        if (content.includes('useState') && !content.includes('import') && !content.includes('from \'react\'')) {
            results.errors.push({
                file: relativePath,
                type: 'error',
                message: 'Import React manquant'
            });
            fileHasErrors = true;
        }

        if (fileHasErrors) {
            results.failed++;
        } else {
            results.passed++;
        }

    } catch (error) {
        results.errors.push({
            file: path.relative(frontendPath, filePath),
            type: 'error',
            message: `Erreur de lecture: ${error.message}`
        });
        results.failed++;
    }
}

function scanDirectory(dir) {
    try {
        const files = fs.readdirSync(dir);
        
        files.forEach(file => {
            const filePath = path.join(dir, file);
            const stat = fs.statSync(filePath);
            
            if (stat.isDirectory()) {
                // Skip node_modules, .next, etc.
                if (!['node_modules', '.next', 'dist', 'build'].includes(file)) {
                    scanDirectory(filePath);
                }
            } else if (file.match(/\.(jsx?|tsx?)$/)) {
                analyzeFile(filePath);
            }
        });
    } catch (error) {
        console.error(`Erreur scan directory: ${error.message}`);
    }
}

console.log('╔═══════════════════════════════════════════════════════════════╗');
console.log('║         AUDIT FRONTEND REACT/NEXT.JS - GAMEZONE              ║');
console.log('╚═══════════════════════════════════════════════════════════════╝');
console.log();

console.log(`Scan du répertoire: ${frontendPath}`);
console.log();

if (!fs.existsSync(frontendPath)) {
    console.error(`❌ ERREUR: Répertoire frontend non trouvé: ${frontendPath}`);
    process.exit(1);
}

scanDirectory(frontendPath);

console.log('════════════════════════════════════════════════════════════════');
console.log('                       RÉSULTATS');
console.log('════════════════════════════════════════════════════════════════');
console.log();
console.log(`📁 Fichiers analysés: ${results.totalFiles}`);
console.log(`   - Fichiers JSX/TSX: ${results.jsxFiles}`);
console.log(`   - Fichiers JS/TS: ${results.jsFiles}`);
console.log();
console.log(`✅ Fichiers sans problème: ${results.passed}`);
console.log(`❌ Fichiers avec erreurs: ${results.failed}`);
console.log(`⚠️  Avertissements: ${results.warnings.length}`);
console.log(`❌ Erreurs: ${results.errors.length}`);
console.log();

if (results.errors.length > 0) {
    console.log('═══ ERREURS DÉTECTÉES ═══');
    results.errors.slice(0, 10).forEach(error => {
        console.log(`❌ ${error.file}`);
        console.log(`   ${error.message}${error.count ? ` (${error.count}x)` : ''}`);
    });
    if (results.errors.length > 10) {
        console.log(`   ... et ${results.errors.length - 10} autres erreurs`);
    }
    console.log();
}

if (results.warnings.length > 0 && results.warnings.length <= 20) {
    console.log('═══ AVERTISSEMENTS ═══');
    results.warnings.slice(0, 10).forEach(warning => {
        console.log(`⚠️  ${warning.file}`);
        console.log(`   ${warning.message}${warning.count ? ` (${warning.count}x)` : ''}`);
    });
    if (results.warnings.length > 10) {
        console.log(`   ... et ${results.warnings.length - 10} autres avertissements`);
    }
    console.log();
}

const percentage = results.totalFiles > 0 
    ? Math.round((results.passed / results.totalFiles) * 100) 
    : 0;

console.log('════════════════════════════════════════════════════════════════');
console.log(`TAUX DE RÉUSSITE: ${percentage}% (${results.passed}/${results.totalFiles})`);
console.log('════════════════════════════════════════════════════════════════');

if (percentage === 100) {
    console.log();
    console.log('🎉 EXCELLENT! Frontend sans erreurs critiques!');
} else if (percentage >= 90) {
    console.log();
    console.log('✅ TRÈS BON! Quelques optimisations recommandées.');
} else if (percentage >= 70) {
    console.log();
    console.log('⚠️  BON! Des corrections sont nécessaires.');
} else {
    console.log();
    console.log('❌ ATTENTION! Corrections importantes requises.');
}

process.exit(results.errors.length > 0 ? 1 : 0);
