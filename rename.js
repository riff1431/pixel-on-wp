const fs = require('fs');
const path = require('path');

const replacements = [
    { regex: /PixelOnWP/gi, replacement: 'PixelOnWP' },
    { regex: /PixelOnWP/gi, replacement: 'PixelOnWP' },
    { regex: /PixelOnWP/gi, replacement: 'PixelOnWP' },
    { regex: /pixel_on_wp/g, replacement: 'pixel_on_wp' },
    { regex: /pixel-on-wp/g, replacement: 'pixel-on-wp' },
    { regex: /PixelOnWP_/gi, replacement: 'pixelonwp_' },
    { regex: /pixel-on-wp/g, replacement: 'pixel-on-wp' },
    { regex: /PixelOnWP/g, replacement: 'PixelOnWP' },
    { regex: /PixelOnWP/g, replacement: 'PixelOnWP' },
    { regex: /PixelOnWP/g, replacement: 'pixelonwp' },
    { regex: /pixel_on_wp/g, replacement: 'pixel_on_wp' }
];

function processDirectory(directory) {
    const files = fs.readdirSync(directory);
    
    for (const file of files) {
        const fullPath = path.join(directory, file);
        const stat = fs.statSync(fullPath);
        
        // Skip node_modules or .git or the script itself
        if (file === 'node_modules' || file === '.git' || file === 'rename.js') continue;
        
        if (stat.isDirectory()) {
            processDirectory(fullPath);
        } else if (stat.isFile() && /\.(php|js|css|txt|md|html)$/.test(file)) {
            let content = fs.readFileSync(fullPath, 'utf8');
            let original = content;
            
            for (const { regex, replacement } of replacements) {
                content = content.replace(regex, function(match) {
                     if (match === 'pixel_on_wp' || match === 'pixel_on_wp') return 'PixelOnWP_';
                     if (match.startsWith('PixelOnWP_')) return 'PixelOnWP_';
                     if (match.startsWith('PixelOnWP_')) return 'PixelOnWp_';
                     if (match.startsWith('PixelOnWP_')) return 'pixelonwp_';
                     
                     if (match === 'PixelOnWP' || match === 'PixelOnWP' || match === 'PixelOnWP') return 'PixelOnWP';
                     
                     return replacement;
                });
            }
            
            if (content !== original) {
                fs.writeFileSync(fullPath, content, 'utf8');
                console.log('Updated:', fullPath);
            }
        }
    }
}

processDirectory(__dirname);
console.log('Replacement complete.');
