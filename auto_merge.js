const { execSync } = require('child_process');
const fs = require('fs');

const branches = [
    'origin/phinguyen',
    'origin/cuong1',
    'origin/dung',
    'origin/thang'
];

function run(cmd) {
    try {
        console.log(`Running: ${cmd}`);
        return execSync(cmd, { encoding: 'utf8' });
    } catch (e) {
        console.log(`Command failed (expected for conflicts): ${cmd}`);
        return e.stdout;
    }
}

function resolveConflicts() {
    const status = run('git status --porcelain');
    const lines = status.split('\n');
    let hasConflicts = false;

    for (const line of lines) {
        if (line.startsWith('UU ') || line.startsWith('AA ') || line.startsWith('AU ') || line.startsWith('UA ')) {
            hasConflicts = true;
            const file = line.substring(3).trim();
            console.log(`Resolving conflict in: ${file}`);
            
            try {
                let content = fs.readFileSync(file, 'utf8');
                
                // Replace conflict markers, keeping both versions
                content = content.replace(/<<<<<<< HEAD(\r?\n)?/g, '');
                content = content.replace(/=======(\r?\n)?/g, '');
                content = content.replace(/>>>>>>> [^\r\n]+(\r?\n)?/g, '');
                
                fs.writeFileSync(file, content);
                run(`git add "${file}"`);
            } catch (err) {
                console.error(`Error processing file ${file}:`, err);
            }
        }
    }
    return hasConflicts;
}

for (const branch of branches) {
    console.log(`\n--- Merging ${branch} ---`);
    const mergeOutput = run(`git merge ${branch} --no-edit -m "Auto merge ${branch}"`);
    
    if (mergeOutput && mergeOutput.includes('CONFLICT')) {
        console.log(`Conflicts detected, auto-resolving...`);
        resolveConflicts();
        run(`git commit --no-edit -m "Auto resolved conflicts for ${branch}"`);
    } else {
        console.log(`Merged ${branch} successfully without conflicts.`);
    }
}
console.log("\nDone merging all branches.");
