const fs = require('fs');
const sql = fs.readFileSync('/home/papermind/Downloads/project_manggis.sql', 'utf8');

let cleaned = sql
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/--.*$/gm, '')
    .replace(/^#.*$/gm, '')
    .replace(/^\s*SET\s+.*?;/gmi, '')
    .replace(/^\s*START TRANSACTION;/gmi, '')
    .replace(/^\s*COMMIT;/gmi, '');

function extractBody(sql, startPos) {
    let depth = 1, pos = startPos, inStr = false, strChar = null;
    while (depth > 0 && pos < sql.length) {
        const c = sql[pos];
        if ((c === "'" || c === '"' || c === '`') && sql[pos - 1] !== '\\') {
            if (!inStr) { inStr = true; strChar = c; }
            else if (strChar === c) { inStr = false; }
        }
        if (!inStr) {
            if (c === '(') depth++;
            else if (c === ')') depth--;
        }
        pos++;
    }
    return depth === 0 ? sql.substring(startPos, pos - 1) : null;
}

function splitByComma(body) {
    const parts = [];
    let cur = '', d = 0, ins = false, sc = null;
    for (let i = 0; i < body.length; i++) {
        const ch = body[i];
        if ((ch === "'" || ch === '"' || ch === '`') && body[i - 1] !== '\\') {
            if (!ins) { ins = true; sc = ch; }
            else if (sc === ch) { ins = false; }
        }
        if (!ins) {
            if (ch === '(') d++;
            else if (ch === ')') d--;
        }
        if (ch === ',' && d === 0 && !ins) {
            parts.push(cur);
            cur = '';
        } else {
            cur += ch;
        }
    }
    if (cur) parts.push(cur);
    return parts;
}

// Find links table
const tableRegex = /CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:[`"\[]?\w+[`"\]]?\.)?[`"\[]?(\w+)[`"\]]?\s*\(/gi;
let match;
while ((match = tableRegex.exec(cleaned)) !== null) {
    const tableName = match[1];
    if (tableName !== 'links') continue;

    const startPos = match.index + match[0].length;
    const body = extractBody(cleaned, startPos);

    console.log('links body:');
    console.log(body);
    console.log('\n---SPLIT PARTS---');
    
    const parts = splitByComma(body);
    parts.forEach((p, i) => {
        console.log(`[${i}]: ${JSON.stringify(p.trim().substring(0, 120))}`);
    });

    console.log('\n---PARSECOLUMNLINE TEST (new regex)---');
    // New regex test
    parts.forEach((p, i) => {
        const line = p.trim();
        const m = line.match(/^[`"\[]?([\w]+)[`"\]]?\s+(\w+)(?:\s*\(\s*([^)]+)\s*\))?/i);
        if (!m) {
            console.log(`[${i}] FAILED to match: ${JSON.stringify(line.substring(0, 80))}`);
        } else {
            console.log(`[${i}] OK: name=${m[1]}, type=${m[2]}, size=${m[3] || ''}`);
        }
    });

    break;
}
