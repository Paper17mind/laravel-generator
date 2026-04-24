/**
 * SQL DDL to JSON Converter Utility
 * Specialized for MySQL CREATE TABLE statements
 */
const SQLParserUtils = {

    parse(sql) {
        const cleanSql = this.cleanSql(sql);
        const tableBlocks = this.extractTableBlocks(cleanSql);
        const tables = [];
        for (const block of tableBlocks) {
            const table = this.parseTableBlock(block);
            if (table) tables.push(table);
        }
        return { tables };
    },

    cleanSql(sql) {
        return sql
            .replace(/\/\*[\s\S]*?\*\//g, '')
            .replace(/--.*$/gm, '')
            .replace(/^#.*$/gm, '')
            .replace(/^\s*SET\s+.*?;/gmi, '')
            .replace(/^\s*START TRANSACTION;/gmi, '')
            .replace(/^\s*COMMIT;/gmi, '')
            .replace(/\n\s*\n/g, '\n');
    },

    extractTableBlocks(sql) {
        const blocks = [];
        const regex = /CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:[`"\[]?\w+[`"\]]?\.)?[`"\[]?(\w+)[`"\]]?\s*\(/gi;
        let match;
        while ((match = regex.exec(sql)) !== null) {
            const tableName = match[1];
            const startPos = match.index + match[0].length;
            const body = this.extractBalancedParentheses(sql, startPos);
            if (body !== null) {
                blocks.push({ name: tableName, body });
            }
        }
        return blocks;
    },

    extractBalancedParentheses(sql, startPos) {
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
    },

    parseTableBlock(block) {
        const table = { name: block.name, columns: [] };
        const lines = this.splitByComma(block.body);
        for (let line of lines) {
            line = line.trim();
            if (!line) continue;
            const upper = line.replace(/`/g, '').trimStart().toUpperCase();
            if (
                upper.startsWith('PRIMARY KEY') ||
                upper.startsWith('KEY ') ||
                upper.startsWith('UNIQUE') ||
                upper.startsWith('CONSTRAINT') ||
                upper.startsWith('INDEX') ||
                upper.startsWith('FULLTEXT') ||
                upper.startsWith('FOREIGN KEY')
            ) continue;
            const col = this.parseColumnLine(line);
            if (col) table.columns.push(col);
        }
        return table.columns.length > 0 ? table : null;
    },

    splitByComma(text) {
        const parts = [];
        let cur = '', d = 0, ins = false, sc = null;
        for (let i = 0; i < text.length; i++) {
            const ch = text[i];
            if ((ch === "'" || ch === '"' || ch === '`') && text[i - 1] !== '\\') {
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
    },

    parseColumnLine(line) {
        // Strip leading backtick-quoted name or bare name
        // Format: `name` type(...) [UNSIGNED] [NOT NULL] [DEFAULT ...]
        const match = line.match(/^[`"\[]?([\w]+)[`"\]]?\s+(\w+)(?:\s*\(\s*([^)]+)\s*\))?/i);
        if (!match) return null;

        const name = match[1];
        const baseType = match[2].toLowerCase();
        // size is inside parens if captured
        let size = match[3] ? match[3].trim() : '';

        // Detect boolean from tinyint(1)
        const isTinyInt1 = baseType === 'tinyint' && size === '1';

        let typeData = 'string';
        if (isTinyInt1) {
            typeData = 'boolean';
        } else if (baseType === 'bigint') {
            typeData = 'bigInteger';
        } else if (baseType.includes('int')) {
            typeData = 'integer';
        } else if (baseType === 'decimal' || baseType === 'float' || baseType === 'double') {
            typeData = 'decimal';
        } else if (['mediumtext', 'longtext', 'tinytext', 'text', 'blob', 'mediumblob', 'longblob'].includes(baseType)) {
            typeData = 'text';
        } else if (baseType === 'date') {
            typeData = 'date';
        } else if (baseType === 'datetime' || baseType === 'timestamp') {
            typeData = 'timestamp';
        } else if (baseType === 'time') {
            typeData = 'time';
        } else if (baseType === 'year') {
            typeData = 'integer';
        } else if (baseType === 'boolean' || baseType === 'bool') {
            typeData = 'boolean';
        } else if (baseType === 'enum' || baseType === 'set') {
            typeData = 'enum';
        } else if (baseType === 'json') {
            typeData = 'text';
        } else if (baseType === 'char' || baseType === 'varchar') {
            typeData = 'string';
        }

        return { name, typeData, size, relasi: '' };
    }
};
